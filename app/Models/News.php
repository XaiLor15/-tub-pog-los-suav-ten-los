<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = ['title', 'body', 'category', 'tags'];

    protected function casts(): array
    {
        return ['tags' => 'array', 'is_pinned' => 'boolean', 'published_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NewsComment::class)->orderBy('created_at');
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaItem::class, 'record_id')->where('module', 'news')->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /** Admins see drafts too; everyone else only published articles. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $user?->isAdmin() ? $query : $query->published();
    }

    public function scopeFeed(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')->orderByRaw('published_at IS NULL')->orderByDesc('published_at')->orderByDesc('id');
    }
}
