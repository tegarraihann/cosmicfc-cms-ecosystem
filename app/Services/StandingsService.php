<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\Season;
use App\Models\StandingAdjustment;
use App\Models\StandingSnapshot;
use App\Models\StatisticRecalculationLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StandingsService
{
    public function recalculate(Season $season, ?int $userId = null, string $trigger = 'manual', bool $publish = false): array
    {
        $log = StatisticRecalculationLog::create(['season_id' => $season->id, 'trigger' => $trigger, 'status' => 'running', 'requested_by' => $userId, 'started_at' => now()]);
        try {
            $matches = GameMatch::where('season_id', $season->id)->whereIn('status', ['completed', 'walkover'])->where('count_for_standings', true)->whereNotNull('home_score')->whereNotNull('away_score')->orderBy('kickoff_at')->get();
            $inputHash = hash('sha256', json_encode([$season->scoring_rules, $matches->map->only(['id', 'matchday', 'home_club_id', 'away_club_id', 'home_score', 'away_score', 'updated_at']), StandingAdjustment::where('season_id', $season->id)->where('is_active', true)->get()->map->only(['id', 'club_id', 'points', 'effective_matchday', 'updated_at'])]));
            $lastMatchday = max(1, (int) ($matches->max('matchday') ?: $season->current_matchday ?: 1));
            DB::transaction(function () use ($season, $matches, $lastMatchday, $inputHash, $publish) {
                for ($matchday = 1; $matchday <= $lastMatchday; $matchday++) {
                    $rows = $this->calculate($season, $matches->filter(fn ($match) => ($match->matchday ?? 0) <= $matchday), $matchday);
                    foreach ($rows as $row) {
                        StandingSnapshot::updateOrCreate(['season_id' => $season->id, 'matchday' => $matchday, 'club_id' => $row['club_id']], [...$row, 'is_published' => $publish, 'calculated_at' => now(), 'calculation_hash' => $inputHash]);
                    }
                }
            });
            $summary = ['matches' => $matches->count(), 'matchdays' => $lastMatchday, 'clubs' => $season->participants()->where('status', 'active')->count(), 'published' => $publish];
            $log->update(['status' => 'completed', 'input_hash' => $inputHash, 'summary' => $summary, 'completed_at' => now()]);
            $this->forget();

            return $summary;
        } catch (\Throwable $exception) {
            $log->update(['status' => 'failed', 'errors' => ['message' => $exception->getMessage()], 'completed_at' => now()]);
            throw $exception;
        }
    }

    public function calculate(Season $season, Collection $matches, int $matchday): Collection
    {
        $participants = $season->participants()->with('club.logo')->where('status', 'active')->orderBy('display_order')->get();
        $rules = array_merge(['win' => 3, 'draw' => 1, 'loss' => 0, 'tie_breakers' => ['points', 'goal_difference', 'goals_for', 'head_to_head']], $season->scoring_rules ?? []);
        $rows = $participants->mapWithKeys(fn ($participant) => [$participant->club_id => ['club_id' => $participant->club_id, 'seed' => $participant->display_order, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'goals_for' => 0, 'goals_against' => 0, 'goal_difference' => 0, 'base_points' => 0, 'adjustment_points' => 0, 'points' => 0, 'form' => []]]);
        foreach ($matches as $match) {
            if (! $rows->has($match->home_club_id) || ! $rows->has($match->away_club_id)) {
                continue;
            }
            foreach ([[$match->home_club_id, $match->home_score, $match->away_score], [$match->away_club_id, $match->away_score, $match->home_score]] as [$clubId, $for, $against]) {
                $row = $rows[$clubId];
                $row['played']++;
                $row['goals_for'] += $for;
                $row['goals_against'] += $against;
                if ($for > $against) {
                    $row['won']++;
                    $row['base_points'] += $rules['win'];
                    $row['form'][] = 'M';
                } elseif ($for === $against) {
                    $row['drawn']++;
                    $row['base_points'] += $rules['draw'];
                    $row['form'][] = 'S';
                } else {
                    $row['lost']++;
                    $row['base_points'] += $rules['loss'];
                    $row['form'][] = 'K';
                }
                $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
                $row['form'] = array_slice($row['form'], -5);
                $rows[$clubId] = $row;
            }
        }
        $adjustments = StandingAdjustment::where('season_id', $season->id)->where('is_active', true)->where('effective_matchday', '<=', $matchday)->selectRaw('club_id, SUM(points) total')->groupBy('club_id')->pluck('total', 'club_id');
        $rows = $rows->map(function ($row) use ($adjustments) {
            $row['adjustment_points'] = (int) ($adjustments[$row['club_id']] ?? 0);
            $row['points'] = $row['base_points'] + $row['adjustment_points'];

            return $row;
        });
        $headToHead = $this->headToHead($matches, $rules);
        $sorted = $rows->values()->sort(function ($a, $b) use ($rules, $headToHead) {
            foreach ($rules['tie_breakers'] as $rule) {
                $field = ['points' => 'points', 'goal_difference' => 'goal_difference', 'goals_for' => 'goals_for'][$rule] ?? null;
                if ($field && $a[$field] !== $b[$field]) {
                    return $b[$field] <=> $a[$field];
                }
                if ($rule === 'head_to_head') {
                    $ap = $headToHead[$a['club_id']][$b['club_id']] ?? 0;
                    $bp = $headToHead[$b['club_id']][$a['club_id']] ?? 0;
                    if ($ap !== $bp) {
                        return $bp <=> $ap;
                    }
                }
            }

            return $a['seed'] <=> $b['seed'];
        })->values();

        return $sorted->map(fn ($row, $index) => [...collect($row)->except('seed')->all(), 'position' => $index + 1]);
    }

    public function publicData(): array
    {
        if (! Schema::hasTable('standing_snapshots')) {
            return ['standings' => [], 'compact' => [], 'cosmic' => null, 'performance' => []];
        }

        return Cache::remember('standings:public', 600, function () {
            $season = Season::where('is_featured', true)->first();
            if (! $season) {
                return ['standings' => [], 'compact' => [], 'cosmic' => null, 'performance' => []];
            }
            $matchday = StandingSnapshot::where('season_id', $season->id)->where('is_published', true)->max('matchday');
            $rows = StandingSnapshot::with('club.logo')->where('season_id', $season->id)->where('matchday', $matchday)->where('is_published', true)->orderBy('position')->get()->map(fn ($row) => $this->resource($row));
            $cosmic = $rows->firstWhere('cosmic', true);
            $cleanSheets = $cosmic ? GameMatch::where('season_id', $season->id)->whereIn('status', ['completed', 'walkover'])->where(function ($query) use ($cosmic) {
                $query->where(fn ($q) => $q->where('home_club_id', $cosmic['clubId'])->where('away_score', 0))->orWhere(fn ($q) => $q->where('away_club_id', $cosmic['clubId'])->where('home_score', 0));
            })->count() : 0;
            $recentPoints = $cosmic ? collect($cosmic['form'])->sum(fn ($result) => ['M' => 3, 'S' => 1, 'K' => 0][$result]) : 0;

            return ['seasonId' => $season->id, 'season' => $season->name, 'matchday' => $matchday, 'standings' => $rows, 'compact' => $rows->take(5)->values(), 'cosmic' => $cosmic, 'performance' => $cosmic ? ['position' => $cosmic['position'], 'played' => $cosmic['played'], 'points' => $cosmic['points'], 'won' => $cosmic['won'], 'drawn' => $cosmic['drawn'], 'lost' => $cosmic['lost'], 'goalsFor' => $cosmic['goalsFor'], 'goalsAgainst' => $cosmic['goalsAgainst'], 'cleanSheets' => $cleanSheets, 'recentForm' => $cosmic['form'], 'recentPoints' => $recentPoints, 'winRate' => $cosmic['played'] ? round($cosmic['won'] / $cosmic['played'] * 100) : 0] : []];
        });
    }

    public function forget(): void
    {
        Cache::forget('standings:public');
        Cache::forget('players:public');
    }

    public function resource(StandingSnapshot $row): array
    {
        return ['position' => $row->position, 'clubId' => $row->club_id, 'code' => $row->club->short_name, 'name' => $row->club->display_name, 'logo' => $row->club->logo?->url ?: ($row->club->is_cosmic ? '/Assets/Logo/Logo.jpeg' : null), 'cosmic' => $row->club->is_cosmic, 'played' => $row->played, 'won' => $row->won, 'drawn' => $row->drawn, 'lost' => $row->lost, 'goalsFor' => $row->goals_for, 'goalsAgainst' => $row->goals_against, 'goals' => $row->goals_for.'–'.$row->goals_against, 'difference' => ($row->goal_difference > 0 ? '+' : '').$row->goal_difference, 'points' => $row->points, 'adjustment' => $row->adjustment_points, 'form' => $row->form ?? []];
    }

    private function headToHead(Collection $matches, array $rules): array
    {
        $map = [];
        foreach ($matches as $match) {
            $home = $match->home_score > $match->away_score ? $rules['win'] : ($match->home_score === $match->away_score ? $rules['draw'] : $rules['loss']);
            $away = $match->away_score > $match->home_score ? $rules['win'] : ($match->away_score === $match->home_score ? $rules['draw'] : $rules['loss']);
            $map[$match->home_club_id][$match->away_club_id] = ($map[$match->home_club_id][$match->away_club_id] ?? 0) + $home;
            $map[$match->away_club_id][$match->home_club_id] = ($map[$match->away_club_id][$match->home_club_id] ?? 0) + $away;
        }

        return $map;
    }
}
