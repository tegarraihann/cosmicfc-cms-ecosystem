<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StandingAdjustment extends Model
{
    use SoftDeletes;

    protected $fillable = ['season_id', 'club_id', 'points', 'reason', 'reference', 'effective_matchday', 'is_active', 'created_by', 'approved_by', 'approved_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'approved_at' => 'datetime'];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
