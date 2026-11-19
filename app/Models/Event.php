<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'title', 'description', 'category', 'start_date', 'end_date', 'start_time', 'end_time',
        'location', 'organizer', 'is_public',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'is_public' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function pledges(): HasMany
    {
        return $this->hasMany(DonationPledge::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaItem::class, 'record_id')->where('module', 'event')->orderBy('sort_order')->orderBy('id');
    }

    /** Guests and pending users only see public events. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $user?->isMember() ? $query : $query->where('is_public', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereDate('start_date', '>=', today())
            ->orWhereDate('end_date', '>=', today()))->orderBy('start_date')->orderBy('start_time');
    }

    public function timeRange(): string
    {
        $fmt = fn ($t) => $t ? substr($t, 0, 5) : null;
        $s = $fmt($this->start_time);
        $e = $fmt($this->end_time);

        return $s ? ($e ? "$s – $e" : $s) : '—';
    }

    public function dateRange(): string
    {
        $s = lao_date($this->start_date);

        return $this->end_date && ! $this->end_date->equalTo($this->start_date) ? $s.' – '.lao_date($this->end_date) : $s;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? route('media.event-cover', $this) : null;
    }
}
