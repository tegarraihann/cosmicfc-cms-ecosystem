<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerAward extends Model
{
    protected $fillable = ['award_type_id', 'player_id', 'season_id', 'match_id', 'type', 'period_month', 'period_key', 'title', 'editorial', 'quote', 'statistics_snapshot', 'appearances', 'goals', 'assists', 'rating', 'media_id', 'primary_media_id', 'is_published', 'status', 'is_featured', 'published_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['rating' => 'decimal:1', 'statistics_snapshot' => 'array', 'is_published' => 'boolean', 'is_featured' => 'boolean', 'published_at' => 'datetime'];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function awardType(): BelongsTo
    {
        return $this->belongsTo(AwardType::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function primaryMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'primary_media_id');
    }

    public function nominees(): HasMany
    {
        return $this->hasMany(AwardNominee::class);
    }
}
