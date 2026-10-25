<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAudit extends Model
{
    protected $fillable = ['media_id', 'user_id', 'action', 'before_payload', 'after_payload'];

    protected function casts(): array
    {
        return ['before_payload' => 'array', 'after_payload' => 'array'];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }
}
