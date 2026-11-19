<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\MediaItem;
use App\Models\Participant;
use App\Models\Transaction;
use App\Services\AuditLogger;
use App\Services\FinanceService;
use App\Services\MediaService;
use App\Support\Lookups;
use App\Support\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Activities (/activities, /activities/{id}?tab=overview|participants|donations|expenses|photos). */
class ActivityController extends Controller
{
    public const TABS = ['overview' => 'ພາບລວມ', 'participants' => 'ຜູ້ເຂົ້າຮ່ວມ', 'donations' => 'ການບໍລິຈາກ', 'expenses' => 'ລາຍຈ່າຍ', 'photos' => 'ຮູບພາບ'];

    /** Tabs a guest / pending user may open. */
    public const PUBLIC_TABS = ['overview', 'photos'];

    public function __construct(private MediaService $media, private FinanceService $finance) {}

    public function index(Request $request): View
    {
        return $this->page($request, null);
    }

    public function show(Request $request, Event $event): View
    {
        Gate::authorize('events.view', $event);

        return $this->page($request, $event);
    }

    private function page(Request $request, ?Event $selected): View
    {
        $user = $request->user();
        $when = $request->query('when') === 'past' ? 'past' : 'upcoming';
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $request->query('q')).'%';

        $events = Event::query()->visibleTo($user)->withCount('participants')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('location', 'like', $like)))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->when($when === 'upcoming',
                fn ($q) => $q->where(fn ($w) => $w->whereDate('start_date', '>=', today())->orWhereDate('end_date', '>=', today()))->orderBy('start_date')->orderBy('start_time'),
                fn ($q) => $q->whereDate('start_date', '<', today())->where(fn ($w) => $w->whereNull('end_date')->orWhereDate('end_date', '<', today()))->orderByDesc('start_date'))
            ->paginate(20)->withQueryString();

        $year = now()->year;
        $visible = Event::query()->visibleTo($user);
        $data = [
            'events' => $events,
            'when' => $when,
            'yearCount' => (clone $visible)->whereYear('start_date', $year)->count(),
            'upcomingCount' => (clone $visible)->upcoming()->count(),
            'selected' => $selected,
            'canCreate' => Gate::allows('events.create'),
            'editing' => null,
        ];

        if ($selected) {
            $allTabs = Gate::allows('events.view-all-tabs');
            $tab = $request->query('tab', 'overview');
            if (! array_key_exists($tab, self::TABS) || (! $allTabs && ! in_array($tab, self::PUBLIC_TABS, true))) {
                $tab = 'overview';
            }
            $tx = Transaction::where('event_id', $selected->id);
            $summary = [
                'participants' => $selected->participants()->count(),
                'donations' => (int) (clone $tx)->where('type', 'income')->where('source', 'donation')->sum('amount'),
                'income' => (int) (clone $tx)->where('type', 'income')->sum('amount'),
                'expenses' => (int) (clone $tx)->where('type', 'expense')->sum('amount'),
            ];
            $summary['net'] = $summary['income'] - $summary['expenses'];
            $myMemberId = $user?->member_id;

            $data += [
                'tab' => $tab,
                'tabs' => $allTabs ? self::TABS : array_intersect_key(self::TABS, array_flip(self::PUBLIC_TABS)),
                'summary' => $summary,
                'canEdit' => Gate::allows('events.update', $selected),
                'canJoin' => Gate::allows('events.join'),
                'myMemberId' => $myMemberId,
                'joined' => $myMemberId ? $selected->participants()->where('member_id', $myMemberId)->exists() : false,
                'participants' => $tab === 'participants' ? $selected->participants()->with('member')->orderBy('id')->get() : collect(),
                'progress' => $tab === 'donations' ? $this->finance->donationProgress($selected) : collect(),
                'expenses' => $tab === 'expenses' ? Transaction::where('event_id', $selected->id)->where('type', 'expense')->orderByDesc('date')->get() : collect(),
                'media' => $tab === 'photos' ? $selected->media()->get() : collect(),
            ];
        }

        if ($request->filled('edit') && ($e = Event::find($request->integer('edit'))) && Gate::allows('events.update', $e)) {
            $data['editing'] = $e;
        }

        return view('activities.index', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('events.create');
        $event = new Event($this->validated($request));
        $event->created_by = $request->user()->id;
        $event->save();
        $this->saveCover($request, $event);

        return redirect()->route('activities.show', $event)->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('events.update', $event);
        $event->update($this->validated($request));
        $this->saveCover($request, $event);

        return redirect()->route('activities.show', $event)->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('events.delete', $event);
        if ($event->pledges()->exists() || $event->transactions()->exists()) {
            return back()->with('error', 'ກິດຈະກຳນີ້ມີຂໍ້ມູນການເງິນ/ການບໍລິຈາກ — ບໍ່ສາມາດລຶບໄດ້');
        }
        DB::transaction(function () use ($event) {
            AuditLogger::log('event_delete', $event, $event->title);
            foreach ($event->media()->get() as $m) {
                $this->media->delete($m->path);
                $m->delete();
            }
            $this->media->delete($event->cover_path);
            $event->delete();
        });

        return redirect()->route('activities.index')->with('success', 'ລຶບກິດຈະກຳແລ້ວ');
    }

    /** Signed-in member registers themself (needs a linked member record). */
    public function join(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('events.join');
        Gate::authorize('events.view', $event);
        $memberId = $request->user()->member_id;
        if (! $memberId) {
            return back()->with('error', 'ບັນຊີຂອງທ່ານຍັງບໍ່ໄດ້ເຊື່ອມກັບຂໍ້ມູນສະມາຊິກ — ກະລຸນາຕິດຕໍ່ຜູ້ດູແລລະບົບ');
        }
        $data = $request->validate(['status' => ['nullable', Rule::in(array_keys(Lookups::PARTICIPANT_STATUS))]]);
        Participant::updateOrCreate(['event_id' => $event->id, 'member_id' => $memberId], ['status' => $data['status'] ?? 'confirmed']);

        return back()->with('success', 'ລົງຊື່ເຂົ້າຮ່ວມແລ້ວ');
    }

    public function leave(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('events.join');
        Participant::where('event_id', $event->id)->where('member_id', $request->user()->member_id)->delete();

        return back()->with('success', 'ຍົກເລີກການເຂົ້າຮ່ວມແລ້ວ');
    }

    public function addParticipant(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('events.update', $event);
        $data = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'duty' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(array_keys(Lookups::PARTICIPANT_STATUS))],
        ], ['member_id.required' => 'ກະລຸນາເລືອກສະມາຊິກ']);
        Participant::updateOrCreate(['event_id' => $event->id, 'member_id' => $data['member_id']], ['duty' => $data['duty'] ?? null, 'status' => $data['status']]);

        return redirect()->route('activities.show', [$event, 'tab' => 'participants'])->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function removeParticipant(Event $event, Participant $participant): RedirectResponse
    {
        Gate::authorize('events.update', $event);
        $participant->delete();

        return redirect()->route('activities.show', [$event, 'tab' => 'participants'])->with('success', 'ເອົາອອກແລ້ວ');
    }

    public function uploadMedia(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('events.update', $event);
        $request->validate(['files' => ['required', 'array', 'max:20'], 'files.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm', 'max:51200']],
            ['files.required' => 'ກະລຸນາເລືອກໄຟລ໌', 'files.*.mimetypes' => 'ຮອງຮັບຮູບ JPG/PNG/WebP ຫຼື ວິດີໂອ MP4/WebM']);
        $order = (int) $event->media()->max('sort_order');
        foreach ($request->file('files') as $file) {
            $stored = $this->media->storeMedia($file, 'events/'.$event->id);
            MediaItem::create(['module' => 'event', 'record_id' => $event->id, ...$stored, 'sort_order' => ++$order, 'created_by' => $request->user()->id]);
        }

        return redirect()->route('activities.show', [$event, 'tab' => 'photos'])->with('success', 'ອັບໂຫຼດສຳເລັດ');
    }

    public function deleteMedia(Event $event, MediaItem $mediaItem): RedirectResponse
    {
        Gate::authorize('events.update', $event);
        abort_unless($mediaItem->module === 'event' && $mediaItem->record_id === $event->id, 404);
        $this->media->delete($mediaItem->path);
        $mediaItem->delete();

        return redirect()->route('activities.show', [$event, 'tab' => 'photos'])->with('success', 'ລຶບຮູບແລ້ວ');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $v = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::in(Lookups::EVENT_CATEGORIES)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:200'],
            'organizer' => ['nullable', 'string', 'max:150'],
            'is_public' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'cover' => Rules::image(),
        ], ['title.required' => 'ກະລຸນາໃສ່ຊື່ກິດຈະກຳ', 'start_date.required' => 'ກະລຸນາໃສ່ວັນທີ', 'end_date.after_or_equal' => 'ວັນສິ້ນສຸດຕ້ອງບໍ່ກ່ອນວັນເລີ່ມ']);
        $v['is_public'] = (bool) ($v['is_public'] ?? false);
        unset($v['cover']);

        return $v;
    }

    private function saveCover(Request $request, Event $event): void
    {
        if ($request->hasFile('cover')) {
            $old = $event->cover_path;
            $event->forceFill(['cover_path' => $this->media->storeImage($request->file('cover'), 'events/'.$event->id)['path']])->save();
            $this->media->delete($old);
        }
    }
}
