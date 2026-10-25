<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerSeasonStat extends Model
{
    protected $fillable = ['player_id', 'season_id', 'appearances', 'starts', 'minutes', 'goals', 'assists', 'clean_sheets', 'yellow_cards', 'red_cards', 'rating', 'attendance_percent'];

    protected function casts(): array
    {
        return ['rating' => 'decimal:1'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
