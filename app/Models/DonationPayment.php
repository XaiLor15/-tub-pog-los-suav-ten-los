<?php

namespace App\Models;

use App\Support\Lookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonationPayment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'paid_on' => 'date'];
    }

    public function pledge(): BelongsTo
    {
        return $this->belongsTo(DonationPledge::class, 'pledge_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function methodLabel(): string
    {
        return Lookups::PAYMENT_METHODS[$this->method] ?? $this->method;
    }
}
