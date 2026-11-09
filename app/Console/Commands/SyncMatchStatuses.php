<?php

namespace App\Console\Commands;

use App\Services\MatchReminderService;
use App\Services\MatchStatusService;
use Illuminate\Console\Command;

class SyncMatchStatuses extends Command
{
    protected $signature = 'matches:sync-statuses';

    protected $description = 'Sinkronkan siklus pertandingan dan kirim pengingat hasil secara aman';

    public function handle(MatchStatusService $statuses, MatchReminderService $reminders): int
    {
        $started = $statuses->startDueMatches();
        $notifications = $reminders->sendDueReminders();
        $completed = $statuses->completeDueMatches();

        $this->table(['Proses', 'Jumlah'], [
            ['Mulai otomatis', $started],
            ['Selesai otomatis', $completed],
            ['Telegram terkirim', $notifications['telegram']],
            ['Email terkirim', $notifications['email']],
            ['Ditunda karena admin aktif', $notifications['skipped_active']],
            ['Pengiriman gagal', $notifications['failed']],
        ]);

        return self::SUCCESS;
    }
}
