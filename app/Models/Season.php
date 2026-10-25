<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $fillable = ['competition_id', 'name', 'slug', 'starts_at', 'ends_at', 'is_active', 'format', 'current_matchday', 'total_matchdays', 'max_participants', 'scoring_rules', 'regulations', 'is_featured', 'status', 'published_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean', 'scoring_rules' => 'array', 'regulations' => 'array', 'is_featured' => 'boolean', 'published_at' => 'datetime'];
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CompetitionParticipant::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PlayerSeason::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(PlayerSeasonStat::class);
    }

    public function standingSnapshots(): HasMany
    {
        return $this->hasMany(StandingSnapshot::class);
    }

    public function standingAdjustments(): HasMany
    {
        return $this->hasMany(StandingAdjustment::class);
    }
}
