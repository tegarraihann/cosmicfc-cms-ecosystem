<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerSeason extends Model
{
    protected $table = 'player_season';

    protected $fillable = ['player_id', 'season_id', 'shirt_number', 'primary_position', 'secondary_position', 'is_captain', 'is_featured', 'featured_order', 'squad_status', 'display_order'];

    protected function casts(): array
    {
        return ['is_captain' => 'boolean', 'is_featured' => 'boolean'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
