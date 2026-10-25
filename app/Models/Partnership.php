<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partnership extends Model
{
    protected $fillable = ['sponsor_id', 'season_id', 'tier', 'title', 'starts_at', 'ends_at', 'status', 'internal_notes', 'published_at', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'published_at' => 'datetime'];
    }

    public function sponsor(): BelongsTo { return $this->belongsTo(Sponsor::class); }
    public function season(): BelongsTo { return $this->belongsTo(Season::class); }
    public function placements(): HasMany { return $this->hasMany(PartnerPlacement::class); }
}
