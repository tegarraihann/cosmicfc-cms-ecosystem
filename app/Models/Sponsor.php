<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sponsor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'category', 'description', 'logo_path', 'logo_media_id',
        'light_logo_media_id', 'dark_logo_media_id', 'website_url', 'contact_name',
        'contact_email', 'instagram_url', 'sort_order', 'is_active', 'status',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function logo(): BelongsTo { return $this->belongsTo(Media::class, 'logo_media_id'); }
    public function lightLogo(): BelongsTo { return $this->belongsTo(Media::class, 'light_logo_media_id'); }
    public function darkLogo(): BelongsTo { return $this->belongsTo(Media::class, 'dark_logo_media_id'); }
    public function partnerships(): HasMany { return $this->hasMany(Partnership::class); }
    public function audits(): HasMany { return $this->hasMany(PartnerAudit::class); }
}
