<?php

namespace App\Services;

use App\Models\GameMatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MatchService
{
    public function publicData(): array
    {
        if (! Schema::hasTable('matches')) {
            return ['live' => null, 'next' => null, 'upcoming' => [], 'results' => [], 'matchday' => []];
        }

        return Cache::remember('matches:public', 600, function () {
            $base = GameMatch::with(['season.competition', 'homeClub.logo', 'awayClub.logo', 'cover'])->where('is_public', true);
            $live = (clone $base)->where('status', 'live')->latest('kickoff_at')->first();
            $upcoming = (clone $base)->whereIn('status', ['scheduled', 'postponed'])->orderBy('kickoff_at')->get()->map(fn ($m) => $this->transform($m));
            $results = (clone $base)->whereIn('status', ['completed', 'walkover'])->latest('kickoff_at')->limit(12)->get()->map(fn ($m) => $this->transform($m));
            $next = $upcoming->firstWhere('featured', true) ?? $upcoming->first();
            $featuredSeason = app(CompetitionService::class)->publicData()['season']['id'] ?? null;
            $matchday = $featuredSeason ? (clone $base)->where('season_id', $featuredSeason)->orderBy('kickoff_at')->get()->map(fn ($m) => $this->transform($m)) : collect();

            return ['live' => $live ? $this->transform($live) : null, 'next' => $next, 'upcoming' => $upcoming, 'results' => $results, 'matchday' => $matchday];
        });
    }

    public function detail(string $slug): ?array
    {
        $match = GameMatch::with(['season.competition', 'homeClub.logo', 'awayClub.logo', 'cover', 'lineups.player.photo', 'lineups.club', 'events' => fn ($query) => $query->where('is_public', true), 'events.primaryPlayer', 'events.secondaryPlayer', 'events.club'])->where('slug', $slug)->where('is_public', true)->first();

        return $match ? $this->transform($match, true) : null;
    }

    public function forget(): void
    {
        Cache::forget('matches:public');
    }

    private function transform(GameMatch $m, bool $detail = false): array
    {
        $club = fn ($c) => ['id' => $c->id, 'name' => $c->display_name, 'shortName' => $c->short_name, 'logo' => $c->logo?->url ?: ($c->is_cosmic ? '/Assets/Logo/Logo.jpeg' : null), 'color' => $c->primary_color];
        $typeLabel = ['competitive' => 'Kompetisi', 'friendly' => 'Friendly', 'sparing' => 'Sparing', 'tournament' => 'Turnamen', 'exhibition' => 'Ekshibisi'][$m->match_type] ?? ucfirst($m->match_type);
        $data = ['id' => $m->id, 'slug' => $m->slug, 'href' => route('matches.show', $m->slug, false), 'type' => $m->match_type, 'typeLabel' => $typeLabel, 'matchday' => $m->matchday, 'round' => $m->round_name, 'competition' => $m->season?->competition?->name ?? $typeLabel, 'season' => $m->season?->name, 'kickoff' => $m->kickoff_at?->toIso8601String(), 'date' => $m->kickoff_at?->translatedFormat('d M Y'), 'time' => $m->kickoff_at?->format('H.i').' WIB', 'venue' => $m->venue_name, 'address' => $m->venue_address, 'status' => $m->status, 'scoreMode' => $m->score_mode, 'home' => $club($m->homeClub), 'away' => $club($m->awayClub), 'homeScore' => $m->home_score, 'awayScore' => $m->away_score, 'homePenaltyScore' => $m->home_penalty_score, 'awayPenaltyScore' => $m->away_penalty_score, 'score' => $m->home_score !== null ? "{$m->home_score} - {$m->away_score}" : null, 'penaltyScore' => $m->home_penalty_score !== null ? "{$m->home_penalty_score} - {$m->away_penalty_score}" : null, 'featured' => $m->is_featured, 'cover' => $m->cover?->url, 'report' => $m->short_report, 'attendance' => $m->attendance];
        if ($detail) {
            $data['lineups'] = $m->lineups->groupBy('club_id')->map(fn ($rows) => $rows->map(fn ($l) => ['id' => $l->id, 'name' => $l->player?->display_name ?? $l->guest_name, 'number' => $l->shirt_number, 'position' => $l->position, 'role' => $l->role, 'captain' => $l->is_captain, 'photo' => $l->player?->photo?->url])->values());
            $data['events'] = $m->events->map(fn ($event) => app(MatchEventService::class)->resource($event))->values();
            $data['reconciliation'] = app(MatchEventService::class)->reconciliation($m);
        }

        return $data;
    }
}
