<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwardNominee extends Model
{
    protected $fillable = ['player_award_id', 'player_id', 'statistics_snapshot', 'nomination_note', 'display_order'];

    protected function casts(): array
    {
        return ['statistics_snapshot' => 'array'];
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(PlayerAward::class, 'player_award_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
