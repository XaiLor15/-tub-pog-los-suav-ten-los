<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    protected $fillable = ['date', 'description', 'category', 'type', 'amount', 'event_id'];

    protected function casts(): array
    {
        return ['date' => 'date', 'amount' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function donationPayment(): HasOne
    {
        return $this->hasOne(DonationPayment::class);
    }

    public function isFromDonation(): bool
    {
        return $this->source === 'donation';
    }

    public function signedAmount(): int
    {
        return $this->type === 'expense' ? -$this->amount : $this->amount;
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to));
    }
}
