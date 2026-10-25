<?php

namespace App\Services;

use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CompetitionService
{
    public function publicData(): array
    {
        if (! Schema::hasTable('competitions')) {
            return ['competition' => null, 'season' => null, 'participants' => [], 'regulations' => []];
        }

        return Cache::remember('competitions:public', 3600, function () {
            $season = Season::with(['competition.logo', 'competition.banner', 'participants.club.logo'])->where('is_featured', true)->whereIn('status', ['scheduled', 'ongoing', 'completed'])->first();
            if (! $season) {
                return ['competition' => null, 'season' => null, 'participants' => [], 'regulations' => []];
            }$competition = $season->competition;

            return ['competition' => ['id' => $competition->id, 'name' => $competition->name, 'shortName' => $competition->short_name, 'slug' => $competition->slug, 'type' => $competition->type, 'organizer' => $competition->organizer, 'description' => $competition->description, 'logo' => $competition->logo?->url, 'banner' => $competition->banner?->url], 'season' => ['id' => $season->id, 'name' => $season->name, 'slug' => $season->slug, 'startsAt' => $season->starts_at?->format('Y-m-d'), 'endsAt' => $season->ends_at?->format('Y-m-d'), 'format' => $season->format, 'currentMatchday' => $season->current_matchday, 'totalMatchdays' => $season->total_matchdays, 'status' => $season->status, 'scoringRules' => $season->scoring_rules], 'participants' => $season->participants->where('status', 'active')->sortBy('display_order')->map(fn ($p) => ['id' => $p->club->id, 'name' => $p->club->display_name, 'shortName' => $p->club->short_name, 'logo' => $p->club->logo?->url, 'city' => $p->club->city, 'seed' => $p->seed, 'group' => $p->group_name, 'primaryColor' => $p->club->primary_color])->values(), 'regulations' => collect($season->regulations ?? [])->filter(fn ($rule) => $rule['active'] ?? true)->values()];
        });
    }

    public function forget(): void
    {
        Cache::forget('competitions:public');
    }
}
