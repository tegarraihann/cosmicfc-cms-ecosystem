<?php

namespace App\Console\Commands;

use App\Models\SystemProcessRun;
use App\Services\MatchReminderService;
use App\Services\MatchStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncMatchStatuses extends Command
{
    protected $signature = 'matches:sync-statuses';

    protected $description = 'Sinkronkan siklus pertandingan dan kirim pengingat hasil secara aman';

    public function handle(MatchStatusService $statuses, MatchReminderService $reminders): int
    {
        $run = Schema::hasTable('system_process_runs') ? SystemProcessRun::create([
            'process' => 'matches:sync-statuses',
            'status' => 'running',
            'started_at' => now(),
        ]) : null;

        try {
            $started = $statuses->startDueMatches();
            $notifications = $reminders->sendDueReminders();
            $completed = $statuses->completeDueMatches();

            $summary = [
                'started' => $started,
                'completed' => $completed,
                ...$notifications,
            ];

            $run?->update(['status' => 'success', 'finished_at' => now(), 'summary' => $summary]);
        } catch (\Throwable $exception) {
            $run?->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_summary' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }

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
