<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatisticRecalculationLog extends Model
{
    protected $fillable = ['season_id', 'trigger', 'status', 'input_hash', 'summary', 'errors', 'requested_by', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['summary' => 'array', 'errors' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
