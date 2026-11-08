<?php

namespace App\Services;

use App\Models\ClubProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ClubProfileService
{
    public function published(): array
    {
        if (! Schema::hasTable('club_profiles')) {
            return $this->fallback();
        }

        $profile = Cache::rememberForever(
            'club-profile:published',
            fn () => $this->transform(ClubProfile::where('status', 'published')->first()),
        );

        return $this->withSeoDefaults($profile);
    }

    public function draft(): array
    {
        if (! Schema::hasTable('club_profiles')) {
            return $this->fallback();
        }

        return $this->transform(ClubProfile::where('status', 'draft')->first());
    }

    public function forget(): void
    {
        Cache::forget('club-profile:published');
    }

    public function defaults(): array
    {
        return ['club_name' => 'COSMIC', 'short_name' => 'COSMIC', 'tagline' => null, 'short_description' => null, 'founded_at' => null, 'founded_year' => null, 'city' => null, 'country' => null, 'home_base' => null, 'address' => null, 'map_url' => null, 'email' => null, 'phone' => null, 'whatsapp' => null, 'contact_person' => null, 'logo_url' => null, 'alternate_logo_url' => null, 'favicon_url' => null, 'share_image_url' => null, 'primary_color' => '#07111e', 'accent_color' => '#45b7ff', 'socials' => [], 'seo' => $this->seoDefaults(), 'website' => ['timezone' => 'Asia/Jakarta', 'locale' => 'id', 'maintenance' => false, 'analytics_id' => '']];
    }

    private function transform(?ClubProfile $profile): array
    {
        $data = $profile?->toArray() ?? $this->defaults();
        $foundedAt = $profile?->founded_at;
        $data = $this->withSeoDefaults($data);

        $data['founded_at'] = $foundedAt?->format('Y-m-d') ?? ($data['founded_at'] ?? null);
        $data['identity'] = ['name' => $data['club_name'], 'shortName' => $data['short_name'], 'tagline' => $data['tagline'], 'description' => $data['short_description'], 'foundedYear' => $foundedAt?->year ?? $data['founded_year']];
        $data['branding'] = ['logo' => $data['logo_url'], 'alternateLogo' => $data['alternate_logo_url'] ?? null, 'favicon' => $data['favicon_url'] ?? null, 'shareImage' => $data['share_image_url'] ?? null, 'primaryColor' => $data['primary_color'], 'accentColor' => $data['accent_color']];
        $data['location'] = ['city' => $data['city'], 'country' => $data['country'], 'homeBase' => $data['home_base'], 'address' => $data['address'] ?? null, 'mapUrl' => $data['map_url'] ?? null];
        $data['contact'] = ['email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null, 'whatsapp' => $data['whatsapp'] ?? null, 'person' => $data['contact_person'] ?? null];

        return $data;
    }

    private function seoDefaults(): array
    {
        return [
            'default_title' => '',
            'default_description' => '',
            'keywords' => '',
            'indexing_enabled' => true,
            'pages' => array_fill_keys(['home', 'schedule', 'league', 'team', 'news'], [
                'title' => '',
                'description' => '',
            ]),
        ];
    }

    private function withSeoDefaults(array $data): array
    {
        $data['seo'] = array_replace_recursive(
            $this->seoDefaults(),
            is_array($data['seo'] ?? null) ? $data['seo'] : [],
        );

        return $data;
    }

    private function fallback(): array
    {
        return $this->transform(new ClubProfile($this->defaults()));
    }
}
