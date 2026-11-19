<?php

namespace App\Http\Controllers;

use App\Models\Elder;
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

class ElderController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index(Request $request): View
    {
        return $this->page($request, $request->integer('id') ?: null);
    }

    public function show(Request $request, Elder $elder): View
    {
        return $this->page($request, $elder->id);
    }

    private function page(Request $request, ?int $selectedId): View
    {
        $all = Elder::with(['member', 'successors'])->get();
        $alive = $all->filter(fn ($e) => $e->member?->is_alive);
        $ages = $alive->map(fn ($e) => $e->member->age())->filter();

        $list = $all
            ->when($request->filled('cat'), fn ($c) => $c->where('role_category', $request->query('cat')))
            ->when($request->filled('q'), fn ($c) => $c->filter(fn ($e) => str_contains(mb_strtolower($e->member?->name.' '.$e->member?->hmong_name.' '.$e->role_title), mb_strtolower($request->query('q')))))
            ->sortBy([fn ($a, $b) => ($b->member?->is_alive <=> $a->member?->is_alive), fn ($a, $b) => $a->member?->generation <=> $b->member?->generation])
            ->values();

        return view('elders.index', [
            'elders' => $list,
            'counts' => $all->countBy('role_category'),
            'total' => $all->count(),
            'alive' => $alive->count(),
            'avgAge' => $ages->isNotEmpty() ? (int) round($ages->avg()) : null,
            'successors' => $all->sum(fn ($e) => $e->successors->count()),
            'withoutSuccessor' => $all->filter(fn ($e) => $e->successors->isEmpty())->count(),
            'categories' => $all->pluck('role_category')->unique()->count(),
            'selected' => $selectedId ? $all->firstWhere('id', $selectedId) : null,
            'private' => Gate::allows('view-private'),
            'canManage' => Gate::allows('elders.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('elders.manage');
        [$data, $successors] = $this->validated($request, null);
        $elder = DB::transaction(function () use ($data, $successors) {
            $e = Elder::create($data);
            $e->successors()->sync($successors);

            return $e;
        });
        $this->savePhoto($request, $elder);
        AuditLogger::log('elder_change', $elder, 'ເພີ່ມຜູ້ອາວຸໂສ '.$elder->member->name);

        return redirect()->route('elders.show', $elder)->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function update(Request $request, Elder $elder): RedirectResponse
    {
        Gate::authorize('elders.manage');
        [$data, $successors] = $this->validated($request, $elder);
        DB::transaction(function () use ($elder, $data, $successors) {
            $elder->update($data);
            $elder->successors()->sync($successors);
        });
        $this->savePhoto($request, $elder);
        AuditLogger::log('elder_change', $elder, 'ແກ້ໄຂຜູ້ອາວຸໂສ '.$elder->member->name);

        return redirect()->route('elders.show', $elder)->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function destroy(Elder $elder): RedirectResponse
    {
        Gate::authorize('elders.manage');
        AuditLogger::log('elder_change', $elder, 'ເອົາອອກຈາກຜູ້ອາວຸໂສ '.$elder->member?->name);
        $this->media->delete($elder->photo_path);
        $elder->delete();

        return redirect()->route('elders.index')->with('success', 'ລຶບແລ້ວ (ຂໍ້ມູນສະມາຊິກຍັງຢູ່)');
    }

    public function export(): StreamedResponse
    {
        $rows = Elder::with(['member', 'successors'])->get()->map(fn (Elder $e) => [
            $e->member?->name, $e->role_title, $e->categoryLabel(), 'Phaj '.$e->member?->generation, $e->member?->lifeSpan(),
            $e->member?->is_alive ? 'ມີຊີວິດ' : 'ເສຍຊີວິດ', implode(', ', $e->skills ?? []), $e->successors->pluck('name')->implode(', '),
            $e->member?->phone ? Format::phone($e->member->phone) : '',
        ]);

        return Csv::download('elders', ['ຊື່', 'ບົດບາດ', 'ໝວດ', 'Phaj', 'ປີເກີດ–ເສຍ', 'ສະຖານະ', 'ຄວາມຊຳນານ', 'ຜູ້ສືບທອດ', 'ເບີໂທ'], $rows);
    }

    /** @return array{0: array<string, mixed>, 1: array<int, int>} */
    private function validated(Request $request, ?Elder $elder): array
    {
        $v = $request->validate([
            'member_id' => ['required', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at'), Rule::unique('elders', 'member_id')->ignore($elder?->id)],
            'role_title' => ['required', 'string', 'max:100'],
            'role_category' => ['required', Rule::in(array_keys(Lookups::ELDER_CATEGORIES))],
            'bio' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['active', 'retired'])],
            'successors' => ['nullable', 'array', 'max:20'],
            'successors.*' => ['integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'photo' => Rules::image(),
        ], ['member_id.unique' => 'ສະມາຊິກນີ້ເປັນຜູ້ອາວຸໂສແລ້ວ', 'member_id.required' => 'ກະລຸນາເລືອກສະມາຊິກ', 'role_title.required' => 'ກະລຸນາໃສ່ບົດບາດ']);

        $skills = collect(preg_split('/[,،\n]+/', (string) ($v['skills'] ?? '')))->map(fn ($s) => trim($s))->filter()->unique()->values()->all();
        $successors = collect($v['successors'] ?? [])->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === (int) $v['member_id'])->unique()->values()->all();

        return [[
            'member_id' => $v['member_id'], 'role_title' => $v['role_title'], 'role_category' => $v['role_category'],
            'bio' => $v['bio'] ?? null, 'skills' => $skills, 'status' => $v['status'] ?? 'active',
        ], $successors];
    }

    private function savePhoto(Request $request, Elder $elder): void
    {
        if ($request->hasFile('photo')) {
            $old = $elder->photo_path;
            $elder->forceFill(['photo_path' => $this->media->storeImage($request->file('photo'), 'elders')['path']])->save();
            $this->media->delete($old);
        }
    }
}
