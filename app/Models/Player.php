<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use SoftDeletes;

    protected $fillable = ['uuid', 'slug', 'name', 'number', 'position', 'is_captain', 'is_featured', 'featured_order', 'display_order', 'is_active', 'full_name', 'display_name', 'birth_date', 'height_cm', 'dominant_foot', 'hometown', 'biography', 'primary_media_id', 'alternate_media_id', 'image_focal_x', 'image_focal_y', 'status', 'seo', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'is_captain' => 'boolean', 'is_featured' => 'boolean', 'is_active' => 'boolean', 'seo' => 'array'];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'primary_media_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(PlayerSeason::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(PlayerSeasonStat::class);
    }

    public function awards(): HasMany
    {
        return $this->hasMany(PlayerAward::class);
    }
}
