<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use SoftDeletes;

    protected $table = 'staff';

    protected $fillable = ['uuid', 'slug', 'full_name', 'display_name', 'hometown', 'biography', 'primary_media_id', 'alternate_media_id', 'image_focal_x', 'image_focal_y', 'status', 'social_links', 'seo', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['social_links' => 'array', 'seo' => 'array'];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'primary_media_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(StaffAssignment::class);
    }

    public function mediaUsages(): HasMany
    {
        return $this->hasMany(MediaUsage::class, 'usable_id')->where('usable_type', self::class);
    }
}
