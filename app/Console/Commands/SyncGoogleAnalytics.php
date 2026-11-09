<?php

namespace App\Console\Commands;

use App\Models\AnalyticsSetting;
use App\Services\GoogleAnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncGoogleAnalytics extends Command
{
    protected $signature = 'analytics:sync';

    protected $description = 'Memperbarui cache ringkasan Google Analytics 4';

    public function handle(GoogleAnalyticsService $analytics): int
    {
        if (! Schema::hasTable('analytics_settings') || ! AnalyticsSetting::query()->where('enabled', true)->exists()) {
            $this->components->info('GA4 belum aktif. Sinkronisasi dilewati.');

            return self::SUCCESS;
        }

        $result = $analytics->summary(true);
        if (! $result['available']) {
            $this->components->error($result['status']['lastError'] ?? 'Sinkronisasi GA4 gagal.');

            return self::FAILURE;
        }

        $this->components->info('Ringkasan GA4 berhasil diperbarui.');

        return self::SUCCESS;
    }
}
