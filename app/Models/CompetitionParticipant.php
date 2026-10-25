<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionParticipant extends Model
{
    protected $fillable = ['season_id', 'club_id', 'seed', 'group_name', 'display_order', 'joined_at', 'status'];

    protected function casts(): array
    {
        return ['joined_at' => 'date'];
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
