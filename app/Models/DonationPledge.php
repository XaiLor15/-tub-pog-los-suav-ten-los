<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DonationPledge extends Model
{
    protected $fillable = ['member_id', 'event_id', 'expected_amount'];

    protected function casts(): array
    {
        return ['expected_amount' => 'integer'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class)->withTrashed();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DonationPayment::class, 'pledge_id')->orderByDesc('paid_on')->orderByDesc('id');
    }
}
