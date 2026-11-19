<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Writes the audit trail (Dev Handoff `audit_*` triggers): member delete/unlink/reorder,
 * role changes, approvals, all finance C/U/D and donation payments.
 */
class AuditLogger
{
    /** action => [Lao label, module label, tone] */
    public const ACTIONS = [
        'member_create' => ['ເພີ່ມສະມາຊິກ', 'ສະມາຊິກ', 'info'],
        'member_update' => ['ແກ້ໄຂສະມາຊິກ', 'ສະມາຊິກ', 'info'],
        'member_delete' => ['ລຶບສະມາຊິກ (Soft delete)', 'ສະມາຊິກ', 'destructive'],
        'member_unlink' => ['ເອົາອອກຈາກສາຍພໍ່', 'ສະມາຊິກ', 'destructive'],
        'member_reorder' => ['ຈັດລຳດັບລູກ', 'ສະມາຊິກ', 'info'],
        'user_approve' => ['ອະນຸມັດຜູ້ໃຊ້', 'ຜູ້ໃຊ້', 'success'],
        'user_reject' => ['ປະຕິເສດຜູ້ໃຊ້', 'ຜູ້ໃຊ້', 'destructive'],
        'role_change' => ['ປ່ຽນ Role', 'ຜູ້ໃຊ້', 'primary'],
        'user_link_member' => ['ເຊື່ອມບັນຊີກັບສະມາຊິກ', 'ຜູ້ໃຊ້', 'primary'],
        'user_disable' => ['ປິດບັນຊີ', 'ຜູ້ໃຊ້', 'destructive'],
        'user_enable' => ['ເປີດບັນຊີ', 'ຜູ້ໃຊ້', 'success'],
        'tx_create' => ['ບັນທຶກທຸລະກຳ', 'ການເງິນ', 'destructive'],
        'tx_update' => ['ແກ້ໄຂທຸລະກຳ', 'ການເງິນ', 'destructive'],
        'tx_delete' => ['ລຶບທຸລະກຳ', 'ການເງິນ', 'destructive'],
        'payment_create' => ['ບັນທຶກການຊຳລະ', 'ບໍລິຈາກ', 'success'],
        'payment_update' => ['ແກ້ໄຂການຊຳລະ', 'ບໍລິຈາກ', 'success'],
        'payment_delete' => ['ລຶບການຊຳລະ', 'ບໍລິຈາກ', 'destructive'],
        'pledge_create' => ['ເພີ່ມຜູ້ບໍລິຈາກ', 'ບໍລິຈາກ', 'success'],
        'pledge_update' => ['ແກ້ໄຂຍອດຄາດຫວັງ', 'ບໍລິຈາກ', 'success'],
        'pledge_delete' => ['ລຶບຜູ້ບໍລິຈາກ', 'ບໍລິຈາກ', 'destructive'],
        'fund_add' => ['ເພີ່ມຜູ້ຮ່ວມກອງທຶນ', 'ກອງທຶນ', 'success'],
        'fund_remove' => ['ລຶບຜູ້ຮ່ວມກອງທຶນ', 'ກອງທຶນ', 'destructive'],
        'leadership_change' => ['ແກ້ໄຂຄະນະນຳ', 'ຄະນະນຳ', 'primary'],
        'elder_change' => ['ແກ້ໄຂຜູ້ອາວຸໂສ', 'ຜູ້ອາວຸໂສ', 'primary'],
        'event_delete' => ['ລຶບກິດຈະກຳ', 'ກິດຈະກຳ', 'destructive'],
        'news_delete' => ['ລຶບຂ່າວ', 'ຂ່າວ', 'destructive'],
    ];

    public static function log(string $action, ?Model $entity = null, ?string $summary = null, array $meta = [], ?User $actor = null): void
    {
        $actor ??= auth()->user();

        AuditLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'entity' => $entity ? Str::snake(class_basename($entity)) : null,
            'entity_id' => $entity?->getKey(),
            'summary' => $summary ? Str::limit($summary, 495) : null,
            'meta' => $meta ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    public static function label(string $action): string
    {
        return self::ACTIONS[$action][0] ?? $action;
    }

    public static function module(string $action): string
    {
        return self::ACTIONS[$action][1] ?? '—';
    }

    public static function tone(string $action): string
    {
        return self::ACTIONS[$action][2] ?? 'neutral';
    }
}
