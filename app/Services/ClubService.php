<?php

namespace App\Services;

use App\Models\Club;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ClubService
{
    public function publicData(): array
    {
        if (! Schema::hasTable('clubs')) {
            return ['cosmic' => null, 'clubs' => []];
        }

        return Cache::remember('clubs:public', 3600, function () {
            $clubs = Club::with('logo')->where('status', 'active')->orderByDesc('is_cosmic')->orderBy('display_name')->get()->map(fn ($club) => ['id' => $club->id, 'slug' => $club->slug, 'name' => $club->display_name, 'officialName' => $club->official_name, 'shortName' => $club->short_name, 'type' => $club->type, 'city' => $club->city, 'country' => $club->country, 'logo' => $club->logo?->url, 'primaryColor' => $club->primary_color, 'secondaryColor' => $club->secondary_color, 'isCosmic' => $club->is_cosmic]);

            return ['cosmic' => $clubs->firstWhere('isCosmic', true), 'clubs' => $clubs->values()];
        });
    }

    public function forget(): void
    {
        Cache::forget('clubs:public');
    }

    public static function normalize(string $value): string
    {
        return str($value)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '')->toString();
    }
}
