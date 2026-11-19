<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Display formatting rules from the Dev Handoff (Design tokens → ຮູບແບບ):
 *  - Money:   1.250.000 ₭  (dot thousands separator, no decimals)
 *  - Percent: 61,5%        (comma decimal separator)
 *  - Date:    15/11/2026
 *  - Phone:   +856 20 5512 3344
 */
final class Format
{
    public const CURRENCY = '₭';

    /** Lao vowels that are written before the consonant they follow phonetically. */
    private const LEADING_VOWELS = ['ເ', 'ແ', 'ໂ', 'ໃ', 'ໄ'];

    public static function number(int|float|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 0, ',', '.');
    }

    public static function money(int|float|string|null $amount, bool $signed = false): string
    {
        $amount = (float) ($amount ?? 0);
        $text = self::number(abs($amount)).' '.self::CURRENCY;
        if (! $signed) {
            return ($amount < 0 ? '− ' : '').$text;
        }

        return ($amount < 0 ? '− ' : '+ ').$text;
    }

    public static function percent(int|float|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '—';
        }
        $rounded = round((float) $value, $decimals);
        if ($rounded == (int) $rounded) {
            return (int) $rounded.'%';
        }

        return number_format($rounded, $decimals, ',', '.').'%';
    }

    public static function date(mixed $date, bool $withTime = false): string
    {
        if (! $date) {
            return '—';
        }
        if (! $date instanceof CarbonInterface) {
            $date = Carbon::parse($date);
        }

        return $date->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
    }

    /**
     * Normalise a Lao phone number to E.164 (+856...). Returns null for empty input
     * and the cleaned (but unvalidated) string when it cannot be normalised.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $phone);
        if ($digits === '' || $digits === null) {
            return null;
        }
        if (str_starts_with($digits, '+856')) {
            return $digits;
        }
        if (str_starts_with($digits, '00856')) {
            return '+'.substr($digits, 2);
        }
        if (str_starts_with($digits, '856') && strlen($digits) >= 10) {
            return '+'.$digits;
        }
        if (str_starts_with($digits, '0')) {
            return '+856'.substr($digits, 1);
        }
        if (preg_match('/^(20\d{8}|30\d{7})$/', $digits)) {
            return '+856'.$digits;
        }

        return $digits;
    }

    /** Valid Lao numbers: mobile 20 + 8 digits, 30 + 7 digits, landline 21–89 + 6 digits. */
    public static function isValidPhone(?string $normalized): bool
    {
        return (bool) preg_match('/^\+856(20\d{8}|30\d{7}|[2-8][1-9]\d{6})$/', (string) $normalized);
    }

    public static function phone(?string $phone): string
    {
        if (! $phone) {
            return '—';
        }
        $n = self::normalizePhone($phone);
        if (! self::isValidPhone($n)) {
            return $phone;
        }
        $local = substr($n, 4);
        if (str_starts_with($local, '20')) {
            return '+856 20 '.substr($local, 2, 4).' '.substr($local, 6);
        }
        if (str_starts_with($local, '30')) {
            return '+856 30 '.substr($local, 2, 3).' '.substr($local, 5);
        }

        return '+856 '.substr($local, 0, 2).' '.substr($local, 2, 3).' '.substr($local, 5);
    }

    public const MONTHS = [1 => 'ມັງກອນ', 'ກຸມພາ', 'ມີນາ', 'ເມສາ', 'ພຶດສະພາ', 'ມິຖຸນາ', 'ກໍລະກົດ', 'ສິງຫາ', 'ກັນຍາ', 'ຕຸລາ', 'ພະຈິກ', 'ທັນວາ'];

    public const WEEKDAYS = ['ວັນອາທິດ', 'ວັນຈັນ', 'ວັນອັງຄານ', 'ວັນພຸດ', 'ວັນພະຫັດ', 'ວັນສຸກ', 'ວັນເສົາ'];

    public static function monthName(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }

    public static function weekday(CarbonInterface $date): string
    {
        return self::WEEKDAYS[$date->dayOfWeek];
    }

    /** "2 ຊົ່ວໂມງກ່ອນ", "ມື້ວານ", "3 ວັນກ່ອນ" … */
    public static function ago(mixed $date): string
    {
        if (! $date) {
            return '—';
        }
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $mins = (int) $date->diffInMinutes(now());
        if ($mins < 1) {
            return 'ຫາກໍ່';
        }
        if ($mins < 60) {
            return $mins.' ນາທີກ່ອນ';
        }
        if ($date->isToday()) {
            return intdiv($mins, 60).' ຊົ່ວໂມງກ່ອນ';
        }
        if ($date->isYesterday()) {
            return 'ມື້ວານ';
        }
        $days = (int) $date->copy()->startOfDay()->diffInDays(today());
        if ($days < 7) {
            return $days.' ວັນກ່ອນ';
        }
        if ($days < 30) {
            return intdiv($days, 7).' ອາທິດກ່ອນ';
        }

        return self::date($date);
    }

    /** First consonant of a Lao name (skipping leading vowels), or first Latin letter. */
    public static function initial(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }
        foreach (mb_str_split($name) as $char) {
            if (in_array($char, self::LEADING_VOWELS, true)) {
                continue;
            }

            return mb_strtoupper($char);
        }

        return '?';
    }
}
