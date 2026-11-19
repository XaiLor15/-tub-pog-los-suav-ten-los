<?php

namespace App\Support;

/** Shared validation rules (Zod schema equivalents from the Dev Handoff). */
final class Rules
{
    /** ≥ 8 characters, confirmed. */
    public static function password(): array
    {
        return ['required', 'string', 'min:8', 'max:200', 'confirmed'];
    }

    /** Money: whole Kip, > 0. */
    public static function amount(): array
    {
        return ['required', 'integer', 'min:1', 'max:99999999999999'];
    }

    public static function image(): array
    {
        return ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:10240'];
    }

    /** Lao messages for common rules. */
    public static function messages(): array
    {
        return [
            'name.required' => 'ກະລຸນາໃສ່ຊື່',
            'name.max' => 'ຊື່ຍາວເກີນ 100 ຕົວ',
            'amount.min' => 'ຈຳນວນເງິນຕ້ອງຫຼາຍກວ່າ 0',
            'amount.integer' => 'ຈຳນວນເງິນຕ້ອງເປັນຕົວເລກເຕັມ (ບໍ່ມີຈຸດ)',
            'amount.required' => 'ກະລຸນາໃສ່ຈຳນວນເງິນ',
            'password.min' => 'ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 8 ຕົວອັກສອນ',
            'password.confirmed' => 'ການຢືນຢັນລະຫັດຜ່ານບໍ່ກົງກັນ',
            'birth_date.before_or_equal' => 'ວັນເກີດຕ້ອງບໍ່ເກີນມື້ນີ້',
            'photo.max' => 'ຮູບຕ້ອງບໍ່ເກີນ 10 MB',
            'photo.mimetypes' => 'ຮອງຮັບສະເພາະ JPG, PNG ຫຼື WebP',
        ];
    }
}
