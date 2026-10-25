<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchEventAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['match_event_id', 'match_id', 'user_id', 'action', 'before_payload', 'after_payload', 'correction_reason', 'created_at'];

    protected function casts(): array
    {
        return ['before_payload' => 'array', 'after_payload' => 'array', 'created_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(MatchEvent::class, 'match_event_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
