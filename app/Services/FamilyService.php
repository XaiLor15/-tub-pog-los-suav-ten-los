<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Family-tree rules (Dev Handoff "ສາຍພໍ່ ແລະ ອ້າຍນ້ອງ" + RPC list):
 *  - Relationships come only from father_id + birth_order (patrilineal).
 *  - Father must be male; Phaj (generation) = father's Phaj + 1; no cycles.
 *  - birth_order is always 1..n consecutive among a father's living (non-deleted) children.
 *  - Unlinking sets father_id = NULL — the member is never deleted.
 * Every multi-step change runs in one DB transaction with the sibling rows locked.
 */
class FamilyService
{
    private const MAX_DEPTH = 64;

    /**
     * create_member(..., father_id, insert_at)
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $fatherId, ?int $insertAt, ?int $generation, User $actor): Member
    {
        return DB::transaction(function () use ($data, $fatherId, $insertAt, $generation, $actor) {
            $member = new Member($data);
            $member->generation = $this->resolveGeneration($fatherId, $generation);
            $member->father_id = $fatherId;
            $member->created_by = $actor->id;
            $member->updated_by = $actor->id;
            $member->save();

            if ($fatherId) {
                $ids = $this->childIds($fatherId, lock: true)->reject(fn ($id) => $id === $member->id)->values();
                $pos = $insertAt ? max(1, min($insertAt, $ids->count() + 1)) : $ids->count() + 1;
                $ids->splice($pos - 1, 0, [$member->id]);
                $this->resequence($fatherId, $ids->all());
            }

            AuditLogger::log('member_create', $member, $member->name, [], $actor);

            return $member->fresh();
        });
    }

    /**
     * Update details and (optionally) move the member to another father / position.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Member $member, array $data, ?int $fatherId, ?int $insertAt, ?int $generation, User $actor): Member
    {
        return DB::transaction(function () use ($member, $data, $fatherId, $insertAt, $generation, $actor) {
            $member = Member::lockForUpdate()->findOrFail($member->id);

            if (($data['sex'] ?? $member->sex) === 'female' && $this->childIds($member->id)->isNotEmpty()) {
                throw ValidationException::withMessages(['sex' => 'ສະມາຊິກນີ້ມີລູກ (ເປັນພໍ່) — ບໍ່ສາມາດປ່ຽນເພດເປັນຍິງໄດ້']);
            }

            $oldFather = $member->father_id;
            if ($fatherId !== null) {
                $this->assertValidFather($member, $fatherId);
            }

            $member->fill($data);
            $member->father_id = $fatherId;
            $newGeneration = $this->resolveGeneration($fatherId, $fatherId ? null : ($generation ?? $member->generation));
            $generationChanged = $newGeneration !== $member->generation;
            $member->generation = $newGeneration;
            $member->updated_by = $actor->id;

            if ($oldFather !== $fatherId) {
                $member->birth_order = null;
            }
            $member->save();

            if ($oldFather !== $fatherId && $oldFather) {
                $this->resequence($oldFather, $this->childIds($oldFather, lock: true)->all());
            }
            if ($fatherId) {
                $ids = $this->childIds($fatherId, lock: true)->reject(fn ($id) => $id === $member->id)->values();
                $current = $oldFather === $fatherId ? $this->childIds($fatherId)->search($member->id) : false;
                $pos = $insertAt ?: ($current !== false ? $current + 1 : $ids->count() + 1);
                $ids->splice(max(1, min($pos, $ids->count() + 1)) - 1, 0, [$member->id]);
                $this->resequence($fatherId, $ids->all());
            }
            if ($generationChanged) {
                $this->cascadeGeneration($member->id, $newGeneration);
            }

            AuditLogger::log('member_update', $member, $member->name, [], $actor);

            return $member->fresh();
        });
    }

    /** reorder_children(father_id, ids[]) — ids must be exactly the father's current children. */
    public function reorderChildren(int $fatherId, array $ids, User $actor): void
    {
        DB::transaction(function () use ($fatherId, $ids, $actor) {
            $ids = array_map('intval', $ids);
            $current = $this->childIds($fatherId, lock: true)->all();
            $sortedCurrent = $current;
            $sortedNew = $ids;
            sort($sortedCurrent);
            sort($sortedNew);
            if ($sortedCurrent !== $sortedNew || count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['ids' => 'ລາຍການລູກບໍ່ກົງກັບຂໍ້ມູນປັດຈຸບັນ — ກະລຸນາໂຫຼດໜ້າໃໝ່']);
            }
            $this->resequence($fatherId, $ids);
            AuditLogger::log('member_reorder', Member::find($fatherId), 'ຈັດລຳດັບລູກໃໝ່', ['ids' => $ids], $actor);
        });
    }

    /** Move one child up/down by one place. */
    public function moveChild(Member $member, int $direction, User $actor): void
    {
        if (! $member->father_id) {
            return;
        }
        $ids = $this->childIds($member->father_id)->values();
        $i = $ids->search($member->id);
        $j = $i + ($direction < 0 ? -1 : 1);
        if ($i === false || $j < 0 || $j >= $ids->count()) {
            return;
        }
        $arr = $ids->all();
        [$arr[$i], $arr[$j]] = [$arr[$j], $arr[$i]];
        $this->reorderChildren($member->father_id, $arr, $actor);
    }

    /** unlink_from_father(member_id): father_id = NULL; remaining siblings close the gap. Member is kept. */
    public function unlinkFromFather(Member $member, User $actor): Member
    {
        return DB::transaction(function () use ($member, $actor) {
            $member = Member::lockForUpdate()->findOrFail($member->id);
            $oldFather = $member->father_id;
            if (! $oldFather) {
                return $member;
            }
            $member->father_id = null;
            $member->birth_order = null;
            $member->updated_by = $actor->id;
            $member->save();
            $this->resequence($oldFather, $this->childIds($oldFather, lock: true)->all());
            AuditLogger::log('member_unlink', $member, $member->name, ['father_id' => $oldFather], $actor);

            return $member;
        });
    }

    /** Admin soft delete. Refused while the member still has children linked (never orphan silently). */
    public function softDelete(Member $member, User $actor): void
    {
        DB::transaction(function () use ($member, $actor) {
            $member = Member::lockForUpdate()->findOrFail($member->id);
            if ($this->childIds($member->id)->isNotEmpty()) {
                throw ValidationException::withMessages(['member' => 'ສະມາຊິກນີ້ຍັງມີລູກເຊື່ອມຢູ່ — ກະລຸນາຍ້າຍ ຫຼື ເອົາລູກອອກກ່ອນ']);
            }
            if ($member->elder()->exists() || DB::table('leadership')->where('member_id', $member->id)->exists()) {
                throw ValidationException::withMessages(['member' => 'ສະມາຊິກນີ້ຢູ່ໃນຄະນະນຳ ຫຼື ຜູ້ອາວຸໂສ — ກະລຸນາເອົາອອກກ່ອນ']);
            }
            $father = $member->father_id;
            $member->birth_order = null;
            $member->updated_by = $actor->id;
            $member->save();
            $member->delete();
            if ($father) {
                $this->resequence($father, $this->childIds($father, lock: true)->all());
            }
            AuditLogger::log('member_delete', $member, $member->name, [], $actor);
        });
    }

    /** get_siblings(member_id): all children of the same father, ordered, including self. */
    public function siblings(Member $member): Collection
    {
        if (! $member->father_id) {
            return collect([$member]);
        }

        return Member::where('father_id', $member->father_id)
            ->orderByRaw('birth_order IS NULL')->orderBy('birth_order')->orderBy('id')->get();
    }

    /**
     * get_ancestors(member_id): from the root down to the member, each with phaj_ok
     * (child Phaj = father Phaj + 1).
     *
     * @return Collection<int, array{member: Member, phaj_ok: bool}>
     */
    public function ancestors(Member $member): Collection
    {
        $chain = [$member];
        $seen = [$member->id => true];
        $cur = $member;
        while ($cur->father_id && count($chain) < self::MAX_DEPTH) {
            $cur = Member::withTrashed()->find($cur->father_id);
            if (! $cur || isset($seen[$cur->id])) {
                break;
            }
            $seen[$cur->id] = true;
            $chain[] = $cur;
        }
        $chain = array_reverse($chain);

        return collect($chain)->map(fn (Member $m, int $i) => [
            'member' => $m,
            'phaj_ok' => $i === 0 || $m->generation === $chain[$i - 1]->generation + 1,
        ]);
    }

    /** get_descendant_ids(member_id): every descendant via father_id (excludes the member). */
    public function descendantIds(int $memberId): array
    {
        $result = [];
        $frontier = [$memberId];
        for ($depth = 0; $frontier && $depth < self::MAX_DEPTH; $depth++) {
            $frontier = Member::whereIn('father_id', $frontier)->pluck('id')->all();
            $frontier = array_values(array_diff($frontier, $result, [$memberId]));
            array_push($result, ...$frontier);
        }

        return $result;
    }

    /**
     * get_family_tree(root_id, depth): flat node list for the client layout.
     * Roots = members without a father (optionally one root). Daughters are leaves.
     *
     * @return array<int, array<string, mixed>>
     */
    public function tree(?int $rootId = null, int $depth = 6): array
    {
        $roots = $rootId ? Member::whereKey($rootId)->get() : Member::whereNull('father_id')->orderBy('generation')->orderBy('id')->get();
        $nodes = [];
        $frontier = $roots;
        for ($level = 0; $frontier->isNotEmpty() && $level < $depth; $level++) {
            $ids = $frontier->pluck('id')->all();
            $counts = Member::whereIn('father_id', $ids)->selectRaw('father_id, count(*) as c')->groupBy('father_id')->pluck('c', 'father_id');
            foreach ($frontier as $m) {
                $nodes[] = [
                    'id' => $m->id,
                    'name' => $m->name,
                    'sex' => $m->sex,
                    'generation' => $m->generation,
                    'birth_order' => $m->birth_order,
                    'father_id' => $level === 0 ? null : $m->father_id,
                    'years' => $m->lifeSpan(),
                    'is_alive' => $m->is_alive,
                    'child_count' => (int) ($counts[$m->id] ?? 0),
                    'label' => $m->childLabel(),
                ];
            }
            $frontier = Member::whereIn('father_id', $frontier->where('sex', 'male')->pluck('id'))
                ->orderBy('father_id')->orderByRaw('birth_order IS NULL')->orderBy('birth_order')->orderBy('id')->get();
        }

        return $nodes;
    }

    /** find_duplicate_members(name, birth_date): warns, never blocks. */
    public function findDuplicates(string $name, ?string $birthDate, ?int $excludeId = null): Collection
    {
        $name = trim($name);
        if ($name === '') {
            return collect();
        }

        return Member::with('father')
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->when($birthDate, fn ($q) => $q->whereDate('birth_date', $birthDate))
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->limit(5)->get();
    }

    /** Throws when $fatherId cannot be the father of $member (not male, self, or a descendant → cycle). */
    public function assertValidFather(?Member $member, int $fatherId): Member
    {
        $father = Member::find($fatherId);
        if (! $father) {
            throw ValidationException::withMessages(['father_id' => 'ບໍ່ພົບຂໍ້ມູນພໍ່']);
        }
        if ($father->sex !== 'male') {
            throw ValidationException::withMessages(['father_id' => 'ພໍ່ຕ້ອງເປັນເພດຊາຍ']);
        }
        if ($member) {
            if ($father->id === $member->id) {
                throw ValidationException::withMessages(['father_id' => 'ບໍ່ສາມາດເລືອກຕົນເອງເປັນພໍ່ໄດ້']);
            }
            if (in_array($father->id, $this->descendantIds($member->id), true)) {
                throw ValidationException::withMessages(['father_id' => 'ເປັນລູກຫຼານ — ເລືອກບໍ່ໄດ້ (ຈະເກີດສາຍພົວພັນວົງວຽນ)']);
            }
        }

        return $father;
    }

    private function resolveGeneration(?int $fatherId, ?int $generation): int
    {
        if ($fatherId) {
            return $this->assertValidFather(null, $fatherId)->generation + 1;
        }
        if (! $generation || $generation < 1) {
            throw ValidationException::withMessages(['generation' => 'ບໍ່ມີພໍ່ (ຕົ້ນຕະກູນ) — ກະລຸນາໃສ່ Phaj ≥ 1']);
        }

        return $generation;
    }

    /** Ordered ids of a father's current (non-deleted) children. */
    private function childIds(int $fatherId, bool $lock = false): Collection
    {
        $q = Member::where('father_id', $fatherId)->orderByRaw('birth_order IS NULL')->orderBy('birth_order')->orderBy('id');
        if ($lock) {
            $q->lockForUpdate();
        }

        return $q->pluck('id');
    }

    /** Assign birth_order 1..n in the given order. Cleared first so the UNIQUE index never collides. */
    private function resequence(int $fatherId, array $orderedIds): void
    {
        Member::withTrashed()->where('father_id', $fatherId)->update(['birth_order' => null]);
        foreach (array_values($orderedIds) as $i => $id) {
            Member::whereKey($id)->update(['birth_order' => $i + 1]);
        }
    }

    /** Keep Phaj child = father + 1 for the whole subtree after a move. */
    private function cascadeGeneration(int $memberId, int $generation): void
    {
        $frontier = [$memberId];
        for ($depth = 0; $frontier && $depth < self::MAX_DEPTH; $depth++) {
            $generation++;
            $children = Member::withTrashed()->whereIn('father_id', $frontier)->pluck('id')->all();
            if ($children) {
                Member::withTrashed()->whereIn('id', $children)->update(['generation' => $generation]);
            }
            $frontier = $children;
        }
    }
}
