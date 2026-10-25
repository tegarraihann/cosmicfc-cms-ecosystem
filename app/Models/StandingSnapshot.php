<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandingSnapshot extends Model
{
    protected $fillable = ['season_id', 'matchday', 'club_id', 'position', 'played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against', 'goal_difference', 'base_points', 'adjustment_points', 'points', 'form', 'is_published', 'calculated_at', 'calculation_hash'];

    protected function casts(): array
    {
        return ['form' => 'array', 'is_published' => 'boolean', 'calculated_at' => 'datetime'];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
