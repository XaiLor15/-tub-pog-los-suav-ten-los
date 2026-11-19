<?php

namespace App\Models;

use App\Support\Lookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leadership extends Model
{
    protected $table = 'leadership';

    protected $fillable = ['member_id', 'position', 'group', 'term_start', 'term_end'];

    protected function casts(): array
    {
        return ['term_start' => 'date', 'term_end' => 'date', 'sort_order' => 'integer'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function groupLabel(): string
    {
        return Lookups::LEADERSHIP_GROUPS[$this->group] ?? $this->group;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? route('media.leadership', $this) : $this->member?->photoUrl();
    }
}
