<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Support\Lookups;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @property string $role pending|member|admin
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** role, is_disabled, member_id and approval fields are set server-side only. */
    protected $fillable = ['display_name', 'email', 'password', 'avatar_path', 'theme', 'font_scale'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_disabled' => 'boolean',
            'font_scale' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Sidebar badge (pending approvals) is cached briefly; refresh it when accounts change.
        static::saved(fn () => Cache::forget('nav.pending-users'));
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isAdmin(): bool
    {
        return $this->isActive() && $this->role === 'admin';
    }

    /** Member-level access: approved members and admins. */
    public function isMember(): bool
    {
        return $this->isActive() && in_array($this->role, ['member', 'admin'], true);
    }

    public function isPending(): bool
    {
        return $this->role === 'pending';
    }

    public function isActive(): bool
    {
        return ! $this->is_disabled && $this->rejected_at === null;
    }

    public function roleLabel(): string
    {
        return Lookups::ROLES[$this->role] ?? $this->role;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? route('media.avatar', $this) : null;
    }

    public function terminateSessions(): void
    {
        DB::table('sessions')->where('user_id', $this->id)->delete();
        $this->forceFill(['remember_token' => null])->saveQuietly();
    }
}
