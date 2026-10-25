<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameMatch extends Model
{
    use SoftDeletes;

    protected $table = 'matches';

    protected $fillable = ['uuid', 'slug', 'season_id', 'match_type', 'matchday', 'round_name', 'home_club_id', 'away_club_id', 'kickoff_at', 'timezone', 'venue_name', 'venue_address', 'status', 'score_mode', 'home_score', 'away_score', 'home_penalty_score', 'away_penalty_score', 'manual_score_reason', 'count_for_standings', 'is_featured', 'is_public', 'cover_media_id', 'short_report', 'attendance', 'published_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['kickoff_at' => 'datetime', 'count_for_standings' => 'boolean', 'is_featured' => 'boolean', 'is_public' => 'boolean', 'published_at' => 'datetime'];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function homeClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'home_club_id');
    }

    public function awayClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'away_club_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function lineups(): HasMany
    {
        return $this->hasMany(MatchLineup::class, 'match_id')->orderBy('display_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id')->orderBy('minute')->orderBy('added_minute')->orderBy('sort_order');
    }

    public function mvpAssessments(): HasMany
    {
        return $this->hasMany(MatchMvpAssessment::class, 'match_id');
    }

    public function mvpAward(): HasOne
    {
        return $this->hasOne(PlayerAward::class, 'match_id')
            ->whereHas('awardType', fn ($query) => $query->where('slug', 'player-of-the-match'));
    }

    public function mediaUsages(): HasMany
    {
        return $this->hasMany(MediaUsage::class, 'usable_id')->where('usable_type', self::class);
    }
}
