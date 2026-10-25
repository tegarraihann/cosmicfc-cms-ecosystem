<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchAudit;
use App\Models\MatchEvent;
use Illuminate\Validation\ValidationException;

class MatchEventService
{
    public const TYPES = ['goal', 'penalty_goal', 'own_goal', 'missed_penalty', 'yellow_card', 'second_yellow', 'red_card', 'substitution', 'note'];

    public const PERIODS = ['first_half', 'second_half', 'extra_time_1', 'extra_time_2', 'shootout'];

    public const SCORE_TYPES = ['goal', 'penalty_goal', 'own_goal'];

    public function validateActors(GameMatch $match, array $data): void
    {
        if (! in_array((int) $data['club_id'], [(int) $match->home_club_id, (int) $match->away_club_id], true)) {
            throw ValidationException::withMessages(['club_id' => 'Klub event harus menjadi peserta pertandingan.']);
        }
        if ($data['type'] !== 'note' && empty($data['primary_player_id']) && blank($data['primary_player_name'] ?? null)) {
            throw ValidationException::withMessages(['primary_player_id' => 'Pemain utama atau nama pemain wajib diisi.']);
        }
        if ($data['type'] === 'substitution' && empty($data['secondary_player_id']) && blank($data['secondary_player_name'] ?? null)) {
            throw ValidationException::withMessages(['secondary_player_id' => 'Pemain masuk wajib diisi untuk pergantian.']);
        }
        if (($data['primary_player_id'] ?? null) && $data['primary_player_id'] === ($data['secondary_player_id'] ?? null)) {
            throw ValidationException::withMessages(['secondary_player_id' => 'Pemain utama dan pemain kedua harus berbeda.']);
        }
        $actorClubId = $data['type'] === 'own_goal'
            ? ((int) $data['club_id'] === (int) $match->home_club_id ? $match->away_club_id : $match->home_club_id)
            : $data['club_id'];
        $lineupIds = $match->lineups()->where('club_id', $actorClubId)->whereNotNull('player_id')->pluck('player_id');
        if ($lineupIds->isNotEmpty()) {
            foreach (['primary_player_id', 'secondary_player_id'] as $field) {
                if (($data[$field] ?? null) && ! $lineupIds->contains((int) $data[$field])) {
                    throw ValidationException::withMessages([$field => $data['type'] === 'own_goal'
                        ? 'Pelaku gol bunuh diri harus terdaftar pada susunan pemain klub lawan.'
                        : 'Pemain harus terdaftar pada susunan pemain klub terkait.']);
                }
            }
        }
    }

    public function reconciliation(GameMatch $match): array
    {
        $score = $this->score($match);

        return [...$score, 'homeEvents' => $score['home'], 'awayEvents' => $score['away'], 'homeScore' => $match->home_score, 'awayScore' => $match->away_score, 'mode' => $match->score_mode, 'synced' => $match->score_mode === 'manual' || ($score['home'] === (int) $match->home_score && $score['away'] === (int) $match->away_score)];
    }

    public function score(GameMatch $match): array
    {
        $base = MatchEvent::query()->where('match_id', $match->id);
        $regular = (clone $base)->whereIn('type', self::SCORE_TYPES)->where('period', '!=', 'shootout')->selectRaw('club_id, count(*) total')->groupBy('club_id')->pluck('total', 'club_id');
        $penalties = (clone $base)->whereIn('type', ['goal', 'penalty_goal'])->where('period', 'shootout')->selectRaw('club_id, count(*) total')->groupBy('club_id')->pluck('total', 'club_id');

        return [
            'home' => (int) ($regular[$match->home_club_id] ?? 0),
            'away' => (int) ($regular[$match->away_club_id] ?? 0),
            'homePenalties' => (int) ($penalties[$match->home_club_id] ?? 0),
            'awayPenalties' => (int) ($penalties[$match->away_club_id] ?? 0),
        ];
    }

    public function synchronizeScore(GameMatch $match, ?int $userId = null, string $trigger = 'event_updated'): bool
    {
        $match->refresh();
        if ($match->score_mode !== 'automatic' || $match->status === 'walkover') {
            return false;
        }

        $score = $this->score($match);
        $before = $match->only(['home_score', 'away_score', 'home_penalty_score', 'away_penalty_score']);
        $hasStarted = in_array($match->status, ['live', 'completed']);
        $after = [
            'home_score' => $hasStarted ? $score['home'] : null,
            'away_score' => $hasStarted ? $score['away'] : null,
            'home_penalty_score' => $hasStarted && $score['homePenalties'] ? $score['homePenalties'] : null,
            'away_penalty_score' => $hasStarted && $score['awayPenalties'] ? $score['awayPenalties'] : null,
        ];
        if ($before === $after) {
            return false;
        }

        $match->updateQuietly($after);
        MatchAudit::create(['match_id' => $match->id, 'user_id' => $userId, 'action' => 'score_recalculated', 'before_payload' => $before, 'after_payload' => [...$after, 'trigger' => $trigger]]);

        return true;
    }

    public function resource(MatchEvent $event): array
    {
        return [...$event->only(['id', 'uuid', 'match_id', 'club_id', 'type', 'period', 'minute', 'added_minute', 'primary_player_id', 'primary_player_name', 'secondary_player_id', 'secondary_player_name', 'note', 'is_score_event', 'is_public', 'sort_order']), 'primary_name' => $event->primaryPlayer?->display_name ?? $event->primary_player_name, 'secondary_name' => $event->secondaryPlayer?->display_name ?? $event->secondary_player_name, 'club_name' => $event->club?->display_name, 'minute_label' => $event->minute."'".($event->added_minute ? '+'.$event->added_minute : '')];
    }
}
