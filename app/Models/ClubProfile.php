<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClubProfile extends Model
{
    protected $fillable = ['status', 'version', 'club_name', 'short_name', 'tagline', 'short_description', 'founded_at', 'founded_year', 'city', 'country', 'home_base', 'address', 'map_url', 'email', 'phone', 'whatsapp', 'contact_person', 'logo_url', 'alternate_logo_url', 'favicon_url', 'share_image_url', 'primary_color', 'accent_color', 'socials', 'seo', 'website', 'created_by', 'published_by', 'published_at'];

    protected function casts(): array
    {
        return ['founded_at' => 'date', 'socials' => 'array', 'seo' => 'array', 'website' => 'array', 'published_at' => 'datetime'];
    }
}
