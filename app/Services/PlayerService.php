<?php

namespace App\Services;

use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\PlayerAward;
use App\Models\PlayerSeasonStat;
use App\Models\Season;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PlayerService
{
    public function publicData(): array
    {
        if (! Schema::hasTable('players')) {
            return ['players' => [], 'featured' => [], 'captain' => null, 'playerOfMonth' => null, 'topPlayers' => [], 'squadStats' => [], 'positions' => [], 'season' => null];
        }

        return Cache::remember('players:public', 3600, function () {
            $season = Season::where('is_active', true)->first();
            $playerModels = Player::with('photo')->where('status', 'active')->where('is_active', true)->orderBy('display_order')->orderBy('number')->get();
            $stats = $season ? PlayerSeasonStat::where('season_id', $season->id)->get()->keyBy('player_id') : collect();
            $eventStats = $season && Schema::hasTable('match_events') ? MatchEvent::query()->join('matches', 'matches.id', '=', 'match_events.match_id')->where('matches.season_id', $season->id)->whereIn('matches.status', ['completed', 'walkover'])->where('matches.is_public', true)->where('match_events.is_public', true)->whereNotNull('match_events.primary_player_id')->selectRaw("match_events.primary_player_id, SUM(CASE WHEN match_events.type IN ('goal','penalty_goal') THEN 1 ELSE 0 END) goals, SUM(CASE WHEN match_events.type IN ('yellow_card','second_yellow') THEN 1 ELSE 0 END) yellow_cards, SUM(CASE WHEN match_events.type IN ('red_card','second_yellow') THEN 1 ELSE 0 END) red_cards")->groupBy('match_events.primary_player_id')->get()->keyBy('primary_player_id') : collect();
            $assistStats = $season && Schema::hasTable('match_events') ? MatchEvent::query()->join('matches', 'matches.id', '=', 'match_events.match_id')->where('matches.season_id', $season->id)->whereIn('matches.status', ['completed', 'walkover'])->where('matches.is_public', true)->where('match_events.is_public', true)->whereIn('match_events.type', ['goal', 'penalty_goal'])->whereNotNull('match_events.secondary_player_id')->selectRaw('match_events.secondary_player_id, COUNT(*) assists')->groupBy('match_events.secondary_player_id')->pluck('assists', 'secondary_player_id') : collect();
            $players = $playerModels->map(fn ($player) => $this->transform($player, $stats->get($player->id), $eventStats->get($player->id), (int) ($assistStats[$player->id] ?? 0)))->values();
            $awardHistory = $season ? PlayerAward::with('awardType')->where('season_id', $season->id)->where('status', 'published')->where('published_at', '<=', now())->latest('published_at')->get()->groupBy('player_id') : collect();
            $players = $players->map(function ($player) use ($awardHistory) {
                $player['awards'] = collect($awardHistory[$player['databaseId']] ?? [])->map(fn ($award) => ['id' => $award->id, 'title' => $award->title, 'type' => $award->awardType?->name ?? $award->type, 'period' => $award->period_key ?? $award->period_month])->values();

                return $player;
            });
            $award = $season ? PlayerAward::with(['player.photo', 'primaryMedia'])->where('season_id', $season->id)->where('type', 'player_of_month')->where('status', 'published')->where('published_at', '<=', now())->orderByDesc('is_featured')->orderByDesc('period_key')->latest('published_at')->first() : null;
            $awardStats = $award?->statistics_snapshot ?? ['appearances' => $award?->appearances, 'goals' => $award?->goals, 'assists' => $award?->assists, 'rating' => $award?->rating];
            $month = $award?->player ? [...($players->firstWhere('id', $award->player->slug) ?? []), 'image' => $award->primaryMedia?->url ?? ($players->firstWhere('id', $award->player->slug)['image'] ?? null), 'monthlyStats' => $awardStats, 'awardTitle' => $award->title, 'awardEditorial' => $award->editorial, 'period' => $award->period_key ?? $award->period_month] : null;
            $positionCounts = $players->countBy('position');
            $leaders = collect([['Top Scorer', 'goals', 'Gol'], ['Top Assist', 'assists', 'Assist'], ['Clean Sheet', 'cleanSheets', 'Clean Sheet'], ['Best Rating', 'rating', 'Rating']])->map(function ($rule, $index) use ($players) {
                $p = $players->sortByDesc(fn ($item) => (float) ($item['stats'][$rule[1]] ?? 0))->first();

                return $p ? [...$p, 'category' => $rule[0], 'rank' => '01', 'value' => str_pad((string) ($p['stats'][$rule[1]] ?? 0), 2, '0', STR_PAD_LEFT), 'metric' => $rule[2], 'featured' => $index === 0] : null;
            })->filter()->values();

            return ['season' => $season?->name, 'players' => $players, 'featured' => $players->filter(fn ($p) => $p['featured'])->sortBy('featuredOrder')->take(3)->values(), 'captain' => $players->firstWhere('captain', true), 'playerOfMonth' => $month, 'topPlayers' => $leaders, 'squadStats' => ['players' => $players->count(), 'appearances' => $stats->sum('appearances'), 'goals' => $stats->sum('goals'), 'assists' => $stats->sum('assists'), 'cleanSheets' => $stats->sum('clean_sheets')], 'positions' => collect(['Goalkeeper', 'Defender', 'Midfielder', 'Forward'])->map(fn ($name) => ['name' => $name, 'total' => $positionCounts->get($name, 0), 'width' => $players->count() ? round($positionCounts->get($name, 0) / $players->count() * 100) : 0])->values()];
        });
    }

    public function forget(): void
    {
        Cache::forget('players:public');
    }

    private function transform(Player $p, ?PlayerSeasonStat $stat, mixed $eventStat = null, int $eventAssists = 0): array
    {
        $image = $p->photo?->url ?: '/Assets/Dokumentasi/Docs-wahyu.jpeg';

        return ['id' => $p->slug, 'databaseId' => $p->id, 'name' => $p->display_name, 'fullName' => $p->full_name, 'number' => str_pad((string) $p->number, 2, '0', STR_PAD_LEFT), 'position' => $p->position, 'age' => $p->birth_date?->age, 'height' => $p->height_cm ? "{$p->height_cm} cm" : '-', 'foot' => $p->dominant_foot ?: '-', 'image' => $image, 'photo' => $image, 'imagePosition' => "{$p->image_focal_x}% {$p->image_focal_y}%", 'focalPoint' => "{$p->image_focal_x}% {$p->image_focal_y}%", 'captain' => $p->is_captain, 'featured' => $p->is_featured, 'featuredOrder' => $p->featured_order, 'href' => '/tim', 'summary' => $p->biography, 'stats' => ['appearances' => $stat?->appearances ?? 0, 'goals' => $eventStat ? (int) $eventStat->goals : ($stat?->goals ?? 0), 'assists' => $eventStat || $eventAssists ? $eventAssists : ($stat?->assists ?? 0), 'cleanSheets' => $stat?->clean_sheets ?? 0, 'yellowCards' => $eventStat ? (int) $eventStat->yellow_cards : ($stat?->yellow_cards ?? 0), 'redCards' => $eventStat ? (int) $eventStat->red_cards : ($stat?->red_cards ?? 0), 'rating' => $stat?->rating ?? null, 'attendance' => $stat?->attendance_percent ?? null]];
    }
}
