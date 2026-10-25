<?php

namespace App\Services;

use App\Models\Partnership;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SponsorService
{
    public const PLACEMENTS = [
        'home.official_partners' => 'Beranda · Official Partners',
        'site.footer' => 'Footer Website',
        'league.partners' => 'Halaman Liga',
        'match.detail' => 'Detail Pertandingan',
        'team.apparel' => 'Halaman Tim / Apparel',
        'partners.directory' => 'Direktori Partner',
    ];

    public function publicData(): array
    {
        if (! Schema::hasTable('partnerships')) return ['placements' => [], 'partners' => []];

        return Cache::remember('partners:public', 600, function () {
            $rows = Partnership::query()
                ->with(['sponsor.logo', 'sponsor.lightLogo', 'sponsor.darkLogo', 'placements'])
                ->where('status', 'active')->whereNotNull('published_at')
                ->whereDate('starts_at', '<=', today())
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
                ->whereHas('sponsor', fn ($q) => $q->where('status', 'active')->where('is_active', true))
                ->get();

            $placements = [];
            foreach ($rows as $partnership) {
                $partner = $this->resource($partnership);
                foreach ($partnership->placements->where('is_visible', true) as $placement) {
                    $placements[$placement->placement_key][] = [...$partner, 'displayOrder' => $placement->display_order];
                }
            }
            foreach ($placements as $key => $items) {
                $placements[$key] = collect($items)->sortBy('displayOrder')->values()->all();
            }

            return ['placements' => $placements, 'partners' => $rows->map(fn ($row) => $this->resource($row))->values()];
        });
    }

    public function resource(Partnership $partnership): array
    {
        $sponsor = $partnership->sponsor;
        return [
            'id' => $sponsor->id, 'partnershipId' => $partnership->id,
            'name' => $sponsor->name, 'slug' => $sponsor->slug, 'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $sponsor->name), 0, 3)),
            'category' => $sponsor->category, 'type' => $partnership->title ?: ucfirst($sponsor->category).' Partner',
            'tier' => $partnership->tier, 'description' => $sponsor->description,
            'logo' => $sponsor->logo?->url ?? $sponsor->logo_path,
            'lightLogo' => $sponsor->lightLogo?->url, 'darkLogo' => $sponsor->darkLogo?->url,
            'websiteUrl' => $sponsor->website_url, 'instagramUrl' => $sponsor->instagram_url,
        ];
    }

    public function forget(): void { Cache::forget('partners:public'); }
}
