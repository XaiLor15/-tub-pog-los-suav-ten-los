<?php

namespace App\Models;

use App\Support\Lookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Elder extends Model
{
    protected $fillable = ['member_id', 'role_title', 'role_category', 'bio', 'skills', 'status'];

    protected function casts(): array
    {
        return ['skills' => 'array'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'elder_successors')->withTimestamps();
    }

    public function categoryLabel(): string
    {
        return Lookups::ELDER_CATEGORIES[$this->role_category] ?? $this->role_category;
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? route('media.elder', $this) : $this->member?->photoUrl();
    }
}
