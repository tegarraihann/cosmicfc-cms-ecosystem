<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competition extends Model
{
    use SoftDeletes;

    protected $fillable = ['uuid', 'slug', 'name', 'short_name', 'type', 'organizer', 'description', 'logo_media_id', 'banner_media_id', 'website_url', 'status', 'created_by', 'updated_by'];

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_media_id');
    }

    public function banner(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'banner_media_id');
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    public function mediaUsages(): HasMany
    {
        return $this->hasMany(MediaUsage::class, 'usable_id')->where('usable_type', self::class);
    }
}
