<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchAudit;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchStatusService
{
    public function __construct(
        private readonly MatchEventService $events,
        private readonly MatchService $matches,
        private readonly StandingsService $standings,
    ) {}

    /**
     * Memulai pertandingan terjadwal yang waktu kick-off-nya telah tiba.
     *
     * Status akhir pertandingan tetap menjadi keputusan admin karena durasi
     * aktual pertandingan tidak dapat disimpulkan hanya dari waktu kick-off.
     */
    public function startDueMatches(?CarbonInterface $at = null): int
    {
        $at ??= now();
        $started = 0;

        GameMatch::query()
            ->where('status', 'scheduled')
            ->whereNotNull('kickoff_at')
            ->where('kickoff_at', '<=', $at)
            ->orderBy('id')
            ->chunkById(100, function ($matches) use (&$started, $at): void {
                foreach ($matches as $match) {
                    $changed = DB::transaction(function () use ($match, $at): bool {
                        $before = $match->only(['status', 'is_featured', 'kickoff_at']);

                        $updated = GameMatch::query()
                            ->whereKey($match->id)
                            ->where('status', 'scheduled')
                            ->whereNotNull('kickoff_at')
                            ->where('kickoff_at', '<=', $at)
                            ->update([
                                'status' => 'live',
                                'is_featured' => false,
                                'expected_end_at' => $match->expected_end_at ?? $match->kickoff_at->copy()->addMinutes($match->duration_minutes ?: 120),
                                'updated_at' => now(),
                            ]);

                        if ($updated !== 1) {
                            return false;
                        }

                        $match->refresh();
                        MatchAudit::create([
                            'match_id' => $match->id,
                            'user_id' => null,
                            'action' => 'status_automatically_started',
                            'before_payload' => $before,
                            'after_payload' => [
                                ...$match->only(['status', 'is_featured', 'kickoff_at']),
                                'triggered_at' => $at->toIso8601String(),
                            ],
                        ]);

                        $this->events->synchronizeScore($match, null, 'status_automatically_started');

                        return true;
                    });

                    if ($changed) {
                        $started++;
                    }
                }
            });

        if ($started > 0) {
            $this->matches->forget();
        }

        return $started;
    }

    /** Menutup pertandingan live yang sudah melewati perkiraan durasinya. */
    public function completeDueMatches(?CarbonInterface $at = null): int
    {
        $at ??= now();
        $completed = 0;

        GameMatch::query()
            ->where('status', 'live')
            ->whereNotNull('expected_end_at')
            ->where('expected_end_at', '<=', $at)
            ->orderBy('id')
            ->chunkById(100, function ($matches) use (&$completed, $at): void {
                foreach ($matches as $match) {
                    $changed = DB::transaction(function () use ($match, $at): bool {
                        $this->events->synchronizeScore($match, null, 'status_automatically_completed');
                        $match->refresh();

                        if ($match->home_score === null || $match->away_score === null) {
                            return false;
                        }

                        $before = $match->only(['status', 'home_score', 'away_score', 'expected_end_at']);
                        $updated = GameMatch::query()
                            ->whereKey($match->id)
                            ->where('status', 'live')
                            ->where('expected_end_at', '<=', $at)
                            ->update(['status' => 'completed', 'result_finalized_at' => $at, 'updated_at' => now()]);

                        if ($updated !== 1) {
                            return false;
                        }

                        $match->refresh();
                        MatchAudit::create([
                            'match_id' => $match->id,
                            'user_id' => null,
                            'action' => 'status_automatically_completed',
                            'before_payload' => $before,
                            'after_payload' => [...$match->only(['status', 'home_score', 'away_score', 'result_finalized_at']), 'triggered_at' => $at->toIso8601String()],
                        ]);

                        return true;
                    });

                    if ($changed) {
                        $completed++;
                        $match->refresh();
                        if ($match->season && $match->count_for_standings) {
                            try {
                                $this->standings->recalculate($match->season, null, 'match_automatically_completed', $match->is_public);
                            } catch (\Throwable $exception) {
                                Log::error('Klasemen gagal dihitung setelah pertandingan selesai otomatis.', ['match_id' => $match->id, 'exception' => $exception::class]);
                            }
                        }
                    }
                }
            });

        if ($completed > 0) {
            $this->matches->forget();
        }

        return $completed;
    }
}
