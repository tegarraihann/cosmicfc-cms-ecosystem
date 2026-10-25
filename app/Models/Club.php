<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Club extends Model
{
    use SoftDeletes;

    protected $fillable = ['uuid', 'slug', 'official_name', 'display_name', 'short_name', 'type', 'city', 'country', 'founded_year', 'primary_media_id', 'alternate_media_id', 'primary_color', 'secondary_color', 'website_url', 'instagram_url', 'contact_email', 'default_venue', 'is_cosmic', 'status', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['is_cosmic' => 'boolean'];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'primary_media_id');
    }

    public function alternateLogo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'alternate_media_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(ClubAlias::class);
    }

    public function mediaUsages(): HasMany
    {
        return $this->hasMany(MediaUsage::class, 'usable_id')->where('usable_type', self::class);
    }
}
