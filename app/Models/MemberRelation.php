<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Spouse links (frozen in the UI: read-only, per Dev Handoff Golden Rule 2.1). */
class MemberRelation extends Model
{
    protected $guarded = ['id'];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function related(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'related_member_id');
    }
}
