<?php

namespace App\Support;

/**
 * Fixed lookup lists (Dev Handoff: "ລາຍການຄົງທີ່"). The values used in the design are
 * the defaults here; the family committee can extend these lists in this one file.
 */
final class Lookups
{
    /** 17 provinces + Vientiane Capital. */
    public const PROVINCES = [
        'ນະຄອນຫຼວງວຽງຈັນ', 'ຜົ້ງສາລີ', 'ຫຼວງນ້ຳທາ', 'ອຸດົມໄຊ', 'ບໍ່ແກ້ວ', 'ຫຼວງພະບາງ', 'ຫົວພັນ',
        'ໄຊຍະບູລີ', 'ຊຽງຂວາງ', 'ວຽງຈັນ', 'ບໍລິຄຳໄຊ', 'ຄຳມ່ວນ', 'ສະຫວັນນະເຂດ', 'ສາລະວັນ',
        'ເຊກອງ', 'ຈຳປາສັກ', 'ອັດຕະປື', 'ໄຊສົມບູນ',
    ];

    public const SEX = ['male' => 'ຊາຍ', 'female' => 'ຍິງ'];

    public const EVENT_CATEGORIES = ['ບຸນປະເພນີ', 'ກອງປະຊຸມ', 'ພິທີກຳ', 'ຈິດອາສາ'];

    public const NEWS_CATEGORIES = ['ປະກາດ', 'ກິດຈະກຳ', 'ກອງທຶນ', 'ຍິນດີ'];

    public const INCOME_CATEGORIES = ['ບໍລິຈາກ', 'ຄ່າບຳລຸງ', 'ລາຍຮັບອື່ນ'];

    public const EXPENSE_CATEGORIES = ['ກິດຈະກຳ', 'ອາຫານ', 'ສະຫວັດດີການ', 'ລາຍຈ່າຍອື່ນ'];

    /** Category used for income created by donation payments. */
    public const DONATION_CATEGORY = 'ບໍລິຈາກ';

    public const LEADERSHIP_GROUPS = [
        'executive' => 'ຄະນະປະຈຳ',
        'department_head' => 'ຫົວໜ້າຝ່າຍ',
        'committee' => 'ກຳມະການ',
    ];

    public const ELDER_CATEGORIES = [
        'rite' => 'ພິທີກຳ',
        'qeej' => 'ແຄນ',
        'herb' => 'ຢາພື້ນເມືອງ',
        'history' => 'ປະຫວັດ',
        'craft' => 'ຫັດຖະກຳ',
        'mediate' => 'ໄກ່ເກ່ຍ',
    ];

    public const PARTICIPANT_STATUS = ['confirmed' => 'ຢືນຢັນ', 'maybe' => 'ອາດຈະມາ'];

    public const PAYMENT_METHODS = ['cash' => 'ເງິນສົດ', 'bank' => 'ໂອນຜ່ານທະນາຄານ'];

    public const ROLES = ['pending' => 'ລໍຖ້າອະນຸມັດ', 'member' => 'ສະມາຊິກ', 'admin' => 'ຜູ້ດູແລລະບົບ'];

    /** Generations shown in the UI (DB allows >= 1). */
    public const MAX_UI_GENERATION = 6;

    public static function transactionCategories(): array
    {
        return array_values(array_unique([...self::INCOME_CATEGORIES, ...self::EXPENSE_CATEGORIES]));
    }
}
