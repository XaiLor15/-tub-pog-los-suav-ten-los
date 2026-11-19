<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A family member. `generation` is shown as "Phaj" in the UI.
 * Sibling labels (ອ້າຍ/ເອື້ອຍ/ນ້ອງ) are computed from sex + birth_order, never stored.
 */
class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'hmong_name', 'sex', 'birth_date', 'birth_year_only', 'death_year',
        'is_alive', 'phone', 'province', 'village',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'birth_year_only' => 'boolean',
            'is_alive' => 'boolean',
            'generation' => 'integer',
            'birth_order' => 'integer',
            'death_year' => 'integer',
        ];
    }

    public function father(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'father_id');
    }

    public function mother(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mother_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Member::class, 'father_id')->orderByRaw('birth_order IS NULL')->orderBy('birth_order')->orderBy('id');
    }

    public function spouses(): HasMany
    {
        return $this->hasMany(MemberRelation::class)->where('relation_type', 'spouse');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function elder(): HasOne
    {
        return $this->hasOne(Elder::class);
    }

    public function fundMember(): HasOne
    {
        return $this->hasOne(FundMember::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('hmong_name', 'like', $like));
    }

    public function birthYear(): ?int
    {
        return $this->birth_date?->year;
    }

    /** "1890 – 1965", "1978", or "—". */
    public function lifeSpan(): string
    {
        $born = $this->birthYear();
        if (! $this->is_alive) {
            return ($born ?? '?').' – '.($this->death_year ?? '?');
        }

        return $born ? (string) $born : '—';
    }

    public function age(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }
        $end = $this->is_alive ? now() : ($this->death_year ? now()->setDate($this->death_year, 12, 31) : null);

        return $end ? (int) $this->birth_date->diffInYears($end, true) : null;
    }

    public function sexLabel(): string
    {
        return $this->sex === 'female' ? 'ຍິງ' : 'ຊາຍ';
    }

    /** "ລູກຊາຍຄົນທີ 3", "ລູກສາວຄົນທີ 2", or "ຕົ້ນຕະກູນ". */
    public function childLabel(): string
    {
        if (! $this->father_id) {
            return 'ຕົ້ນຕະກູນ';
        }
        $kind = $this->sex === 'female' ? 'ລູກສາວ' : 'ລູກຊາຍ';

        return $this->birth_order ? "{$kind}ຄົນທີ {$this->birth_order}" : $kind;
    }

    /** Relative sibling label for $other as seen from $this (ອ້າຍ / ເອື້ອຍ / ນ້ອງຊາຍ / ນ້ອງສາວ / ຕົນເອງ). */
    public function siblingLabelFor(Member $other): string
    {
        if ($other->id === $this->id) {
            return 'ຕົນເອງ';
        }
        $older = $other->birth_order !== null && $this->birth_order !== null && $other->birth_order < $this->birth_order;
        if ($older) {
            return $other->sex === 'female' ? 'ເອື້ອຍ' : 'ອ້າຍ';
        }

        return $other->sex === 'female' ? 'ນ້ອງສາວ' : 'ນ້ອງຊາຍ';
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? route('media.member', $this) : null;
    }
}
