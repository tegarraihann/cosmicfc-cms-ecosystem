<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchAudit;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class MatchStatusService
{
    public function __construct(
        private readonly MatchEventService $events,
        private readonly MatchService $matches,
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
}
