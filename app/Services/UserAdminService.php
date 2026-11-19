<?php

namespace App\Services;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * approve_user / reject_user / role changes with the Handoff's guards:
 * protect_last_admin (≥ 1 active admin) and prevent_self_role_change. Every change is audited.
 */
class UserAdminService
{
    public function approve(User $target, ?int $memberId, User $actor): void
    {
        DB::transaction(function () use ($target, $memberId, $actor) {
            $target = User::lockForUpdate()->findOrFail($target->id);
            if (! $target->isPending()) {
                throw ValidationException::withMessages(['user' => 'ບັນຊີນີ້ບໍ່ໄດ້ຢູ່ໃນສະຖານະລໍຖ້າອະນຸມັດ']);
            }
            if ($memberId) {
                $this->assertMemberFree($memberId, $target->id);
                $target->member_id = $memberId;
            }
            $target->role = 'member';
            $target->approved_by = $actor->id;
            $target->approved_at = now();
            $target->rejected_at = null;
            $target->save();
            AuditLogger::log('user_approve', $target, $target->display_name.' ('.$target->email.')'.($memberId ? ' → '.Member::find($memberId)?->name : ''), [], $actor);
        });
        $this->flush();
    }

    public function reject(User $target, User $actor): void
    {
        $this->assertNotSelf($target, $actor);
        DB::transaction(function () use ($target, $actor) {
            $target->forceFill(['rejected_at' => now(), 'is_disabled' => true])->save();
            AuditLogger::log('user_reject', $target, $target->display_name.' ('.$target->email.')', [], $actor);
        });
        $target->terminateSessions();
        $this->flush();
    }

    public function changeRole(User $target, string $role, User $actor): void
    {
        $this->assertNotSelf($target, $actor);
        if (! in_array($role, ['member', 'admin'], true)) {
            throw ValidationException::withMessages(['role' => 'Role ບໍ່ຖືກຕ້ອງ']);
        }
        DB::transaction(function () use ($target, $role, $actor) {
            $target = User::lockForUpdate()->findOrFail($target->id);
            $before = $target->role;
            if ($before === $role) {
                return;
            }
            if ($before === 'admin') {
                $this->assertAnotherAdmin($target);
            }
            $target->role = $role;
            if (! $target->approved_at) {
                $target->approved_at = now();
                $target->approved_by = $actor->id;
            }
            $target->save();
            AuditLogger::log('role_change', $target, $target->display_name.': '.$before.' → '.$role, ['from' => $before, 'to' => $role], $actor);
        });
        $this->flush();
    }

    public function linkMember(User $target, ?int $memberId, User $actor): void
    {
        DB::transaction(function () use ($target, $memberId, $actor) {
            if ($memberId) {
                $this->assertMemberFree($memberId, $target->id);
            }
            $target->forceFill(['member_id' => $memberId])->save();
            AuditLogger::log('user_link_member', $target, $target->display_name.' → '.($memberId ? Member::find($memberId)?->name : 'ຍົກເລີກການເຊື່ອມ'), [], $actor);
        });
    }

    public function toggleDisabled(User $target, User $actor): void
    {
        $this->assertNotSelf($target, $actor);
        DB::transaction(function () use ($target, $actor) {
            $target = User::lockForUpdate()->findOrFail($target->id);
            if (! $target->is_disabled && $target->role === 'admin') {
                $this->assertAnotherAdmin($target);
            }
            $target->is_disabled = ! $target->is_disabled;
            if (! $target->is_disabled) {
                $target->rejected_at = null;
            }
            $target->save();
            AuditLogger::log($target->is_disabled ? 'user_disable' : 'user_enable', $target, $target->display_name, [], $actor);
        });
        if ($target->fresh()->is_disabled) {
            $target->terminateSessions();
        }
        $this->flush();
    }

    /** match_pending_user: members whose name matches the account's display name and are not linked yet. */
    public function suggestions(User $user): Collection
    {
        $name = trim($user->display_name);
        if ($name === '') {
            return collect();
        }
        $first = explode(' ', $name)[0];

        return Member::query()
            ->whereNotIn('id', User::whereNotNull('member_id')->pluck('member_id'))
            ->where(fn ($q) => $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->orWhere('name', 'like', $first.'%')->orWhere('hmong_name', 'like', '%'.$first.'%'))
            ->orderByRaw('CASE WHEN LOWER(name) = ? THEN 0 ELSE 1 END', [mb_strtolower($name)])
            ->limit(3)->get();
    }

    private function assertNotSelf(User $target, User $actor): void
    {
        if ($target->id === $actor->id) {
            throw ValidationException::withMessages(['role' => 'ບໍ່ສາມາດປ່ຽນ Role ຫຼື ສະຖານະຂອງຕົນເອງ']);
        }
    }

    private function assertAnotherAdmin(User $target): void
    {
        $others = User::where('role', 'admin')->where('is_disabled', false)->whereNull('rejected_at')->whereKeyNot($target->id)->count();
        if ($others < 1) {
            throw ValidationException::withMessages(['role' => 'ລະບົບຕ້ອງມີຜູ້ດູແລຢ່າງໜ້ອຍ 1 ຄົນສະເໝີ']);
        }
    }

    private function assertMemberFree(int $memberId, int $userId): void
    {
        if (! Member::whereKey($memberId)->exists()) {
            throw ValidationException::withMessages(['member_id' => 'ບໍ່ພົບຂໍ້ມູນສະມາຊິກ']);
        }
        if (User::where('member_id', $memberId)->whereKeyNot($userId)->exists()) {
            throw ValidationException::withMessages(['member_id' => 'ສະມາຊິກນີ້ເຊື່ອມກັບບັນຊີອື່ນແລ້ວ']);
        }
    }

    private function flush(): void
    {
        Cache::forget('nav.pending-users');
    }
}
