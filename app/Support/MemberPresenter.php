<?php

namespace App\Support;

use App\Models\Member;

/**
 * members_public: what each role may see of a member. Guests and pending users get the
 * birth YEAR only and never the phone number or village (Dev Handoff Spec 6.3 / matrix).
 */
final class MemberPresenter
{
    /** @return array<string, mixed> */
    public static function toArray(Member $m, bool $private): array
    {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'hmong_name' => $m->hmong_name,
            'sex' => $m->sex,
            'generation' => $m->generation,
            'is_alive' => $m->is_alive,
            'father_id' => $m->father_id,
            'birth_order' => $m->birth_order,
            'birth_year' => $m->birthYear(),
            'death_year' => $m->death_year,
            'province' => $m->province,
            'initial' => Format::initial($m->name),
            'label' => $m->childLabel(),
            'birth_date' => $private && $m->birth_date && ! $m->birth_year_only ? $m->birth_date->toDateString() : null,
            'phone' => $private ? $m->phone : null,
            'village' => $private ? $m->village : null,
        ];
    }

    /** Birth text for tables: full date (members) or year only (guests / year-only records). */
    public static function birthText(Member $m, bool $private): string
    {
        if (! $m->birth_date) {
            return '—';
        }
        if (! $private || $m->birth_year_only) {
            return (string) $m->birth_date->year;
        }

        return Format::date($m->birth_date);
    }
}
