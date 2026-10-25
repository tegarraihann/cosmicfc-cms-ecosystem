<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchGame extends Model
{
    protected $fillable = [
        'opponent', 'kickoff_at', 'venue', 'competition', 'status',
        'cosmic_score', 'opponent_score', 'match_events', 'cover_path',
    ];

    protected function casts(): array
    {
        return ['kickoff_at' => 'datetime', 'match_events' => 'array'];
    }
}
