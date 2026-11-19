<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaItem extends Model
{
    protected $guarded = ['id'];

    public function url(): string
    {
        return route('media.item', $this);
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime, 'video/');
    }
}
