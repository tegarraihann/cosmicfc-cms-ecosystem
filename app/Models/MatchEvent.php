<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatchEvent extends Model
{
    use SoftDeletes;

    protected $fillable = ['uuid', 'match_id', 'club_id', 'type', 'period', 'minute', 'added_minute', 'primary_player_id', 'primary_player_name', 'secondary_player_id', 'secondary_player_name', 'note', 'is_score_event', 'is_public', 'sort_order', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_score_event' => 'boolean', 'is_public' => 'boolean'];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function primaryPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'primary_player_id');
    }

    public function secondaryPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'secondary_player_id');
    }
}
