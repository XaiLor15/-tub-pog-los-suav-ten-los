<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Services\FamilyService;
use App\Services\MediaService;
use App\Support\Csv;
use App\Support\Format;
use App\Support\Lookups;
use App\Support\MemberPresenter;
use App\Support\Rules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Members (/members), DetailSheet (/members/{id}), MemberForm dialog (?new=1, ?edit=id),
 * and the family tree (/members?view=tree). Private columns are filtered by MemberPresenter.
 */
class MemberController extends Controller
{
    public function __construct(private FamilyService $family, private MediaService $media) {}

    public function index(Request $request): View
    {
        if ($request->query('view') === 'tree') {
            return view('members.tree', [
                'selectedId' => $request->integer('id') ?: null,
                'canWrite' => Gate::allows('members.write'),
            ]);
        }

        return $this->listing($request, $request->integer('id') ?: null);
    }

    public function show(Request $request, Member $member): View
    {
        return $this->listing($request, $member->id);
    }

    private function listing(Request $request, ?int $selectedId): View
    {
        $private = Gate::allows('view-private');
        $members = $this->filtered($request)->with('father')
            ->orderBy('generation')->orderBy('father_id')->orderByRaw('birth_order IS NULL')->orderBy('birth_order')->orderBy('id')
            ->paginate(20)->withQueryString();

        $selected = $selectedId ? Member::with(['father.father', 'spouses.related'])->find($selectedId) : null;
        $editing = $request->filled('edit') && Gate::allows('members.write') ? Member::with('father')->find($request->integer('edit')) : null;

        return view('members.index', [
            'members' => $members,
            'private' => $private,
            'total' => Member::count(),
            'generations' => Member::distinct()->orderBy('generation')->pluck('generation'),
            'selected' => $selected,
            'siblings' => $selected ? $this->family->siblings($selected) : collect(),
            'children' => $selected ? $selected->children()->get() : collect(),
            'editing' => $editing,
            'openForm' => $request->boolean('new') || $editing || session('reopen_member_form'),
            'canWrite' => Gate::allows('members.write'),
        ]);
    }

    private function filtered(Request $request): Builder
    {
        return Member::query()
            ->search($request->query('q'))
            ->when($request->filled('phaj'), fn ($q) => $q->where('generation', $request->integer('phaj')))
            ->when(in_array($request->query('sex'), ['male', 'female'], true), fn ($q) => $q->where('sex', $request->query('sex')))
            ->when($request->query('status') === 'alive', fn ($q) => $q->where('is_alive', true))
            ->when($request->query('status') === 'dead', fn ($q) => $q->where('is_alive', false));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('members.write');
        [$data, $fatherId, $insertAt, $generation] = $this->validated($request, null);

        $member = $this->family->create($data, $fatherId, $insertAt, $generation, $request->user());
        $this->savePhoto($request, $member);

        return redirect()->route('members.show', $member)->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('members.write');
        [$data, $fatherId, $insertAt, $generation] = $this->validated($request, $member);

        $member = $this->family->update($member, $data, $fatherId, $insertAt, $generation, $request->user());
        $this->savePhoto($request, $member);

        return redirect()->route('members.show', $member)->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function destroy(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('members.delete');
        $this->family->softDelete($member, $request->user());

        return redirect()->route('members.index')->with('success', 'ລຶບສະມາຊິກແລ້ວ (ເກັບປະຫວັດໄວ້)');
    }

    /** Move a child one place up/down among siblings. */
    public function move(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('members.write');
        $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]]);
        $this->family->moveChild($member, $request->input('direction') === 'up' ? -1 : 1, $request->user());

        return back()->with('success', 'ປ່ຽນລຳດັບແລ້ວ');
    }

    /** reorder_children RPC — full ordered list (used by the tree / sibling list). */
    public function reorder(Request $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('members.write');
        $data = $request->validate(['father_id' => ['required', 'integer', 'exists:members,id'], 'ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']]);
        $this->family->reorderChildren((int) $data['father_id'], $data['ids'], $request->user());

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'ປ່ຽນລຳດັບແລ້ວ');
    }

    /** unlink_from_father RPC — father_id = NULL, member kept. */
    public function unlink(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('members.write');
        $this->family->unlinkFromFather($member, $request->user());

        return back()->with('success', 'ເອົາອອກຈາກສາຍພໍ່ແລ້ວ (ບໍ່ໄດ້ລຶບສະມາຊິກ)');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('view-private');
        $rows = $this->filtered($request)->with('father')->orderBy('generation')->orderBy('father_id')->orderBy('birth_order')->lazy(500)
            ->map(fn (Member $m) => [
                $m->id, $m->name, $m->hmong_name, $m->sexLabel(), MemberPresenter::birthText($m, true), 'Phaj '.$m->generation,
                $m->father?->name, $m->childLabel(), $m->is_alive ? 'ມີຊີວິດ' : 'ເສຍຊີວິດ', $m->death_year,
                $m->province, $m->village, Format::phone($m->phone) === '—' ? '' : Format::phone($m->phone),
            ]);

        return Csv::download('members', ['ID', 'ຊື່', 'ຊື່ມົ້ງ', 'ເພດ', 'ວັນເກີດ', 'Phaj', 'ພໍ່', 'ລຳດັບລູກ', 'ສະຖານະ', 'ປີເສຍ', 'ແຂວງ', 'ບ້ານ', 'ເບີໂທ'], $rows);
    }

    /** MemberCombobox data: ?q=&sex=male&for=<memberId> (descendants & self are disabled). */
    public function options(Request $request): JsonResponse
    {
        $for = $request->integer('for') ?: null;
        $blocked = $for ? [$for, ...$this->family->descendantIds($for)] : [];
        $list = Member::search($request->query('q'))
            ->when(in_array($request->query('sex'), ['male', 'female'], true), fn ($q) => $q->where('sex', $request->query('sex')))
            ->with('father')->orderBy('generation')->orderBy('name')->limit(20)->get();

        return response()->json($list->map(fn (Member $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'generation' => $m->generation,
            'sub' => 'Phaj '.$m->generation.($m->father ? ' · ລູກຂອງ '.$m->father->name : ''),
            'initial' => Format::initial($m->name),
            'disabled' => in_array($m->id, $blocked, true) ? ($m->id === $for ? 'ຕົນເອງ' : 'ເປັນລູກຫຼານ — ເລືອກບໍ່ໄດ້') : null,
        ]));
    }

    /** find_duplicate_members RPC (warning only). */
    public function duplicates(Request $request): JsonResponse
    {
        $list = $this->family->findDuplicates((string) $request->query('name'), $request->query('birth_date') ?: null, $request->integer('exclude') ?: null);

        return response()->json($list->map(fn (Member $m) => [
            'id' => $m->id, 'name' => $m->name, 'url' => route('members.show', $m),
            'sub' => 'Phaj '.$m->generation.' · '.MemberPresenter::birthText($m, true).' · '.$m->childLabel().($m->father ? ' ຂອງ '.$m->father->name : ''),
        ]));
    }

    /** Father's current children (birth-order picker in the form). */
    public function children(Member $member): JsonResponse
    {
        return response()->json([
            'father' => ['id' => $member->id, 'name' => $member->name, 'generation' => $member->generation, 'sex' => $member->sex],
            'children' => $member->children()->get()->map(fn (Member $c) => [
                'id' => $c->id, 'name' => $c->name, 'sex' => $c->sex, 'birth_order' => $c->birth_order,
                'year' => $c->birthYear(), 'label' => $c->childLabel(),
            ]),
        ]);
    }

    /** get_family_tree(root_id, depth) */
    public function treeData(Request $request): JsonResponse
    {
        $depth = max(1, min(12, $request->integer('depth', Lookups::MAX_UI_GENERATION)));

        return response()->json(['nodes' => $this->family->tree($request->integer('root') ?: null, $depth)]);
    }

    /** get_ancestors(member_id) + children, for the tree side panel. */
    public function lineage(Member $member): JsonResponse
    {
        return response()->json([
            'member' => MemberPresenter::toArray($member, Gate::allows('view-private')),
            'url' => route('members.show', $member),
            'ancestors' => $this->family->ancestors($member)->map(fn ($a) => [
                'id' => $a['member']->id, 'name' => $a['member']->name, 'generation' => $a['member']->generation,
                'years' => $a['member']->lifeSpan(), 'label' => $a['member']->childLabel(), 'phaj_ok' => $a['phaj_ok'],
                'father_name' => $a['member']->father?->name,
            ])->values(),
            'children' => $member->children()->get()->map(fn (Member $c) => [
                'id' => $c->id, 'name' => $c->name, 'sex' => $c->sex, 'birth_order' => $c->birth_order, 'initial' => Format::initial($c->name),
            ]),
        ]);
    }

    /** @return array{0: array<string, mixed>, 1: ?int, 2: ?int, 3: ?int} */
    private function validated(Request $request, ?Member $member): array
    {
        // Normalise the phone before validating so "020 5577 8899" is accepted and stored as +856…
        $request->merge(['phone' => Format::normalizePhone($request->input('phone'))]);

        $v = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'hmong_name' => ['nullable', 'string', 'max:100'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'birth_year' => ['nullable', 'integer', 'min:1700', 'max:'.now()->year],
            'birth_year_only' => ['nullable', 'boolean'],
            'is_alive' => ['required', 'boolean'],
            'death_year' => ['nullable', 'integer', 'min:1700', 'max:'.now()->year],
            'father_id' => ['nullable', 'integer', 'exists:members,id'],
            'insert_at' => ['nullable', 'integer', 'min:1'],
            'generation' => ['nullable', 'integer', 'min:1', 'max:99'],
            'phone' => ['nullable', 'string', 'max:20', function ($attr, $value, $fail) {
                if ($value && ! Format::isValidPhone($value)) {
                    $fail('ເບີໂທບໍ່ຖືກຕ້ອງ — ຕົວຢ່າງ 020 5512 3344');
                }
            }],
            'province' => ['nullable', Rule::in(Lookups::PROVINCES)],
            'village' => ['nullable', 'string', 'max:100'],
            'photo' => Rules::image(),
            'duplicate_confirmed' => ['nullable', 'boolean'],
        ], [
            ...Rules::messages(),
            'sex.required' => 'ກະລຸນາເລືອກເພດ',
            'birth_date.date' => 'ວັນເກີດບໍ່ຖືກຕ້ອງ',
        ]);

        $yearOnly = (bool) ($v['birth_year_only'] ?? false);
        $birthDate = $yearOnly
            ? (! empty($v['birth_year']) ? sprintf('%04d-01-01', $v['birth_year']) : null)
            : ($v['birth_date'] ?? null);

        if (! $v['is_alive'] && ! empty($v['death_year']) && $birthDate && (int) substr($birthDate, 0, 4) > (int) $v['death_year']) {
            throw ValidationException::withMessages(['death_year' => 'ປີເສຍຊີວິດຕ້ອງບໍ່ກ່ອນປີເກີດ']);
        }

        $dups = $this->family->findDuplicates($v['name'], $birthDate, $member?->id);
        if ($dups->isNotEmpty() && ! $request->boolean('duplicate_confirmed')) {
            session()->flash('reopen_member_form', true);
            session()->flash('member_duplicates', $dups->map(fn ($m) => ['name' => $m->name, 'url' => route('members.show', $m), 'sub' => 'Phaj '.$m->generation.' · '.$m->childLabel()])->all());
            throw ValidationException::withMessages(['duplicate_confirmed' => 'ອາດມີຂໍ້ມູນຊ້ຳ — ກະລຸນາກວດ ແລ້ວໝາຍ “ຂ້ອຍກວດແລ້ວ” ກ່ອນບັນທຶກ']);
        }

        $data = [
            'name' => trim($v['name']),
            'hmong_name' => $v['hmong_name'] ?? null,
            'sex' => $v['sex'],
            'birth_date' => $birthDate,
            'birth_year_only' => $yearOnly,
            'is_alive' => (bool) $v['is_alive'],
            'death_year' => $v['is_alive'] ? null : ($v['death_year'] ?? null),
            'phone' => $v['phone'] ?? null,
            'province' => $v['province'] ?? null,
            'village' => $v['village'] ?? null,
        ];

        return [$data, $v['father_id'] ?? null, $v['insert_at'] ?? null, $v['generation'] ?? null];
    }

    private function savePhoto(Request $request, Member $member): void
    {
        if (! $request->hasFile('photo')) {
            return;
        }
        $stored = $this->media->storeImage($request->file('photo'), 'members');
        $old = $member->photo_path;
        $member->forceFill(['photo_path' => $stored['path']])->save();
        $this->media->delete($old);
    }
}
