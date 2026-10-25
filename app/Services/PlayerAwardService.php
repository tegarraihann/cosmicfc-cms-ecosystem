<?php

namespace App\Services;

use App\Models\Player;
use App\Models\PlayerAward;
use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PlayerAwardService
{
    public function candidates(Season $season): array
    {
        $public = app(PlayerService::class)->publicData();

        return collect($public['players'] ?? [])->map(fn ($player) => ['id' => $player['databaseId'], 'name' => $player['name'], 'number' => $player['number'], 'position' => $player['position'], 'image' => $player['image'], 'statistics' => $player['stats']])->values()->all();
    }

    public function snapshot(Player $player, Season $season): array
    {
        $candidate = collect($this->candidates($season))->firstWhere('id', $player->id);

        return $candidate['statistics'] ?? ['appearances' => 0, 'goals' => 0, 'assists' => 0, 'cleanSheets' => 0, 'rating' => null];
    }

    public function publicData(): array
    {
        if (! Schema::hasTable('award_types')) {
            return ['featured' => null, 'awards' => []];
        }

        return Cache::remember('player-awards:public', 600, function () {
            $awards = PlayerAward::with(['awardType', 'player.photo', 'primaryMedia', 'match.homeClub', 'match.awayClub'])->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())->latest('published_at')->get()->map(fn ($award) => $this->resource($award));

            return ['featured' => $awards->firstWhere('featured', true) ?? $awards->first(), 'awards' => $awards];
        });
    }

    public function matchAward(int $matchId): ?array
    {
        $award = PlayerAward::with(['awardType', 'player.photo', 'primaryMedia'])->where('match_id', $matchId)->where('status', 'published')->where('published_at', '<=', now())->latest()->first();

        return $award ? $this->resource($award) : null;
    }

    public function resource(PlayerAward $award): array
    {
        $stats = $award->statistics_snapshot ?? ['appearances' => $award->appearances, 'goals' => $award->goals, 'assists' => $award->assists, 'rating' => $award->rating];

        return ['id' => $award->id, 'type' => $award->awardType?->slug ?? $award->type, 'typeName' => $award->awardType?->name ?? $award->title, 'title' => $award->title, 'period' => $award->period_key ?? $award->period_month, 'editorial' => $award->editorial, 'quote' => $award->quote, 'featured' => $award->is_featured, 'player' => ['id' => $award->player->id, 'slug' => $award->player->slug, 'name' => $award->player->display_name, 'number' => $award->player->memberships()->where('season_id', $award->season_id)->value('shirt_number'), 'position' => $award->player->memberships()->where('season_id', $award->season_id)->value('primary_position'), 'image' => $award->primaryMedia?->url ?? $award->player->photo?->url ?? '/Assets/Dokumentasi/Docs-wahyu.jpeg'], 'statistics' => $stats, 'matchId' => $award->match_id, 'publishedAt' => $award->published_at?->toIso8601String()];
    }

    public function forget(): void
    {
        Cache::forget('player-awards:public');
        app(PlayerService::class)->forget();
    }
}
