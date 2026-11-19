<?php

namespace App\Http\Controllers;

use App\Models\Leadership;
use App\Services\AuditLogger;
use App\Services\MediaService;
use App\Support\Csv;
use App\Support\Format;
use App\Support\Lookups;
use App\Support\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadershipController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index(Request $request): View
    {
        $leaders = Leadership::with('member')->orderBy('sort_order')->orderBy('id')->get();
        $start = $leaders->min('term_start');
        $end = $leaders->max('term_end');
        $progress = null;
        $remaining = null;
        if ($start && $end && $end->gt($start)) {
            $progress = max(0, min(100, $start->diffInDays(now()) / $start->diffInDays($end) * 100));
            if ($end->isFuture()) {
                $diff = now()->diff($end);
                $remaining = 'ຍັງເຫຼືອ '.($diff->y ? $diff->y.' ປີ ' : '').$diff->m.' ເດືອນ';
            } else {
                $remaining = 'ໝົດວາລະແລ້ວ';
            }
        }

        return view('leadership.index', [
            'view' => $request->query('view') === 'list' ? 'list' : 'chart',
            'leaders' => $leaders,
            'groups' => $leaders->groupBy('group'),
            'termStart' => $start,
            'termEnd' => $end,
            'progress' => $progress,
            'remaining' => $remaining,
            'private' => Gate::allows('view-private'),
            'canManage' => Gate::allows('leadership.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('leadership.manage');
        $data = $this->validated($request);
        $l = new Leadership($data);
        $l->sort_order = (int) Leadership::max('sort_order') + 1;
        $l->save();
        $this->savePhoto($request, $l);
        AuditLogger::log('leadership_change', $l, 'ເພີ່ມ '.$l->position.' — '.$l->member->name);

        return back()->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function update(Request $request, Leadership $leadership): RedirectResponse
    {
        Gate::authorize('leadership.manage');
        $leadership->update($this->validated($request));
        $this->savePhoto($request, $leadership);
        AuditLogger::log('leadership_change', $leadership, 'ແກ້ໄຂ '.$leadership->position.' — '.$leadership->member->name);

        return back()->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function destroy(Leadership $leadership): RedirectResponse
    {
        Gate::authorize('leadership.manage');
        AuditLogger::log('leadership_change', $leadership, 'ລຶບ '.$leadership->position.' — '.$leadership->member?->name);
        $this->media->delete($leadership->photo_path);
        $leadership->delete();

        return back()->with('success', 'ລຶບແລ້ວ');
    }

    /** reorder_leadership(ids[]) — or one step up/down via id + direction. */
    public function reorder(Request $request): RedirectResponse
    {
        Gate::authorize('leadership.manage');
        $data = $request->validate([
            'ids' => ['nullable', 'array'], 'ids.*' => ['integer', 'exists:leadership,id'],
            'id' => ['nullable', 'integer', 'exists:leadership,id'], 'direction' => ['nullable', Rule::in(['up', 'down'])],
        ]);
        DB::transaction(function () use ($data) {
            $ids = Leadership::orderBy('sort_order')->orderBy('id')->lockForUpdate()->pluck('id')->all();
            if (! empty($data['ids'])) {
                $given = array_map('intval', $data['ids']);
                $ids = [...$given, ...array_diff($ids, $given)];
            } elseif (! empty($data['id'])) {
                $i = array_search((int) $data['id'], $ids, true);
                $j = $i + ($data['direction'] === 'up' ? -1 : 1);
                if ($i !== false && $j >= 0 && $j < count($ids)) {
                    [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                }
            }
            foreach (array_values($ids) as $pos => $id) {
                Leadership::whereKey($id)->update(['sort_order' => $pos + 1]);
            }
            AuditLogger::log('leadership_change', null, 'ຈັດລຳດັບຄະນະນຳ');
        });

        return back()->with('success', 'ປ່ຽນລຳດັບແລ້ວ');
    }

    public function export(): StreamedResponse
    {
        $rows = Leadership::with('member')->orderBy('sort_order')->get()->map(fn ($l) => [
            $l->sort_order, $l->member?->name, $l->position, $l->groupLabel(),
            $l->member?->phone ? Format::phone($l->member->phone) : '', Format::date($l->term_start), Format::date($l->term_end),
        ]);

        return Csv::download('leadership', ['ລຳດັບ', 'ຊື່', 'ຕຳແໜ່ງ', 'ກຸ່ມ', 'ເບີໂທ', 'ເລີ່ມວາລະ', 'ຈົບວາລະ'], $rows);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'position' => ['required', 'string', 'max:100'],
            'group' => ['required', Rule::in(array_keys(Lookups::LEADERSHIP_GROUPS))],
            'term_start' => ['nullable', 'date'],
            'term_end' => ['nullable', 'date', 'after_or_equal:term_start'],
            'photo' => Rules::image(),
        ], ['member_id.required' => 'ກະລຸນາເລືອກສະມາຊິກ', 'position.required' => 'ກະລຸນາໃສ່ຕຳແໜ່ງ', 'term_end.after_or_equal' => 'ວັນຈົບວາລະຕ້ອງບໍ່ກ່ອນວັນເລີ່ມ']);
        unset($data['photo']);

        return $data;
    }

    private function savePhoto(Request $request, Leadership $l): void
    {
        if ($request->hasFile('photo')) {
            $old = $l->photo_path;
            $l->forceFill(['photo_path' => $this->media->storeImage($request->file('photo'), 'leadership')['path']])->save();
            $this->media->delete($old);
        }
    }
}
