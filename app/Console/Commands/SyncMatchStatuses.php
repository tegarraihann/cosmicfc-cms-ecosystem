<?php

namespace App\Console\Commands;

use App\Services\MatchStatusService;
use Illuminate\Console\Command;

class SyncMatchStatuses extends Command
{
    protected $signature = 'matches:sync-statuses';

    protected $description = 'Ubah pertandingan terjadwal menjadi sedang berlangsung saat waktu kick-off tercapai';

    public function handle(MatchStatusService $statuses): int
    {
        $started = $statuses->startDueMatches();

        $this->info($started > 0
            ? "{$started} pertandingan diubah menjadi sedang berlangsung."
            : 'Tidak ada status pertandingan yang perlu diperbarui.');

        return self::SUCCESS;
    }
}
