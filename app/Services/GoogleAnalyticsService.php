<?php

namespace App\Services;

use App\Models\AnalyticsSetting;
use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\RunReportRequest;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GoogleAnalyticsService
{
    public function status(): array
    {
        $setting = AnalyticsSetting::current();

        return [
            'enabled' => $setting->enabled,
            'configured' => filled($setting->measurement_id) && filled($setting->property_id) && filled($setting->service_account),
            'connectionStatus' => $setting->connection_status,
            'lastError' => $setting->last_error,
            'lastTestedAt' => $setting->last_tested_at?->locale('id')->translatedFormat('d M Y, H.i').' WIB',
            'lastSyncedAt' => $setting->last_synced_at?->locale('id')->translatedFormat('d M Y, H.i').' WIB',
        ];
    }

    public function summary(bool $refresh = false): array
    {
        $setting = AnalyticsSetting::current();
        if (! $setting->enabled || ! $this->configured($setting)) {
            return ['available' => false, 'status' => $this->status(), 'data' => null];
        }

        $key = 'ga4:summary:'.$setting->id;
        if ($refresh) {
            Cache::forget($key);
        }

        try {
            $data = Cache::remember($key, now()->addMinutes($setting->cache_minutes), fn () => $this->fetchSummary($setting));
            Cache::put($key.':last-success', $data, now()->addDays(7));
            $setting->forceFill(['connection_status' => 'connected', 'last_error' => null, 'last_synced_at' => now()])->save();

            return ['available' => true, 'status' => $this->status(), 'data' => $data];
        } catch (Throwable $exception) {
            report($exception);
            $setting->forceFill(['connection_status' => 'failed', 'last_error' => $this->friendlyError($exception)])->save();

            return ['available' => false, 'status' => $this->status(), 'data' => Cache::get($key.':last-success')];
        }
    }

    public function report(string $startDate, string $endDate, bool $refresh = false): array
    {
        $setting = AnalyticsSetting::current();
        if (! $setting->enabled || ! $this->configured($setting)) {
            return ['available' => false, 'status' => $this->status(), 'data' => null];
        }

        $key = 'ga4:report:'.$setting->id.':'.$startDate.':'.$endDate;
        if ($refresh) {
            Cache::forget($key);
        }

        try {
            $data = Cache::remember($key, now()->addMinutes($setting->cache_minutes), fn () => $this->fetchReport($setting, $startDate, $endDate));
            Cache::put($key.':last-success', $data, now()->addDays(7));
            $setting->forceFill(['connection_status' => 'connected', 'last_error' => null, 'last_synced_at' => now()])->save();

            return ['available' => true, 'status' => $this->status(), 'data' => $data];
        } catch (Throwable $exception) {
            report($exception);
            $setting->forceFill(['connection_status' => 'failed', 'last_error' => $this->friendlyError($exception)])->save();

            return ['available' => false, 'status' => $this->status(), 'data' => Cache::get($key.':last-success')];
        }
    }

    public function testConnection(AnalyticsSetting $setting): void
    {
        $this->run($setting, '7daysAgo', 'today', ['activeUsers'], [], 1);
    }

    private function fetchSummary(AnalyticsSetting $setting): array
    {
        $metrics = $this->metricValues($this->run($setting, '30daysAgo', 'today', ['activeUsers', 'screenPageViews'], [], 1));
        $previous = $this->metricValues($this->run($setting, '60daysAgo', '31daysAgo', ['activeUsers'], [], 1));
        $today = $this->metricValues($this->run($setting, 'today', 'today', ['activeUsers'], [], 1));
        $trend = $this->rows($this->run($setting, '6daysAgo', 'today', ['activeUsers'], ['date'], 7));
        $current = (int) ($metrics[0] ?? 0);
        $previousUsers = (int) ($previous[0] ?? 0);

        return [
            'todayUsers' => (int) ($today[0] ?? 0),
            'users30Days' => $current,
            'pageViews30Days' => (int) ($metrics[1] ?? 0),
            'changePercent' => $previousUsers > 0 ? round((($current - $previousUsers) / $previousUsers) * 100, 1) : null,
            'trend' => collect($trend)->map(fn ($row) => ['date' => $row['dimensions'][0] ?? '', 'value' => (int) ($row['metrics'][0] ?? 0)])->all(),
        ];
    }

    private function fetchReport(AnalyticsSetting $setting, string $startDate, string $endDate): array
    {
        $overview = $this->metricValues($this->run($setting, $startDate, $endDate, ['activeUsers', 'newUsers', 'sessions', 'screenPageViews', 'engagementRate', 'averageSessionDuration'], [], 1));

        return [
            'overview' => [
                'activeUsers' => (int) ($overview[0] ?? 0), 'newUsers' => (int) ($overview[1] ?? 0),
                'sessions' => (int) ($overview[2] ?? 0), 'pageViews' => (int) ($overview[3] ?? 0),
                'engagementRate' => round(((float) ($overview[4] ?? 0)) * 100, 1),
                'averageSessionDuration' => round((float) ($overview[5] ?? 0)),
            ],
            'trend' => $this->mapRows($this->run($setting, $startDate, $endDate, ['activeUsers', 'sessions', 'screenPageViews'], ['date'], 400), ['date'], ['users', 'sessions', 'views']),
            'pages' => $this->mapRows($this->run($setting, $startDate, $endDate, ['screenPageViews', 'activeUsers'], ['pageTitle', 'pagePath'], 10, true), ['title', 'path'], ['views', 'users']),
            'sources' => $this->mapRows($this->run($setting, $startDate, $endDate, ['sessions'], ['sessionDefaultChannelGroup'], 10, true), ['source'], ['sessions']),
            'devices' => $this->mapRows($this->run($setting, $startDate, $endDate, ['activeUsers'], ['deviceCategory'], 10, true), ['device'], ['users']),
            'locations' => $this->mapRows($this->run($setting, $startDate, $endDate, ['activeUsers'], ['country', 'city'], 10, true), ['country', 'city'], ['users']),
            'events' => $this->mapRows($this->run($setting, $startDate, $endDate, ['eventCount'], ['eventName'], 15, true), ['event'], ['count']),
        ];
    }

    private function run(AnalyticsSetting $setting, string $startDate, string $endDate, array $metrics, array $dimensions, int $limit, bool $descending = false): object
    {
        $client = new BetaAnalyticsDataClient(['credentials' => $setting->service_account, 'transport' => 'rest']);
        $request = new RunReportRequest([
            'property' => 'properties/'.$setting->property_id,
            'date_ranges' => [new DateRange(['start_date' => $startDate, 'end_date' => $endDate])],
            'metrics' => array_map(fn ($name) => new Metric(['name' => $name]), $metrics),
            'dimensions' => array_map(fn ($name) => new Dimension(['name' => $name]), $dimensions),
            'limit' => $limit,
        ]);
        if ($descending && $metrics) {
            $request->setOrderBys([new OrderBy(['metric' => new MetricOrderBy(['metric_name' => $metrics[0]]), 'desc' => true])]);
        }

        try {
            return $client->runReport($request, ['timeoutMillis' => 10000]);
        } finally {
            $client->close();
        }
    }

    private function metricValues(object $response): array
    {
        $row = $response->getRows()[0] ?? null;

        return $row ? array_map(fn ($value) => $value->getValue(), iterator_to_array($row->getMetricValues())) : [];
    }

    private function rows(object $response): array
    {
        return array_map(fn ($row) => [
            'dimensions' => array_map(fn ($value) => $value->getValue(), iterator_to_array($row->getDimensionValues())),
            'metrics' => array_map(fn ($value) => $value->getValue(), iterator_to_array($row->getMetricValues())),
        ], iterator_to_array($response->getRows()));
    }

    private function mapRows(object $response, array $dimensionNames, array $metricNames): array
    {
        return array_map(function ($row) use ($dimensionNames, $metricNames) {
            $values = [];
            foreach ($dimensionNames as $index => $name) {
                $values[$name] = $row['dimensions'][$index] ?? '';
            }
            foreach ($metricNames as $index => $name) {
                $values[$name] = is_numeric($row['metrics'][$index] ?? null) ? (float) $row['metrics'][$index] : 0;
            }

            return $values;
        }, $this->rows($response));
    }

    private function configured(AnalyticsSetting $setting): bool
    {
        return filled($setting->measurement_id) && filled($setting->property_id) && filled($setting->service_account);
    }

    private function friendlyError(Throwable $exception): string
    {
        return str_contains(strtolower($exception->getMessage()), 'permission')
            ? 'Service Account belum memiliki akses ke properti GA4.'
            : 'GA4 tidak dapat dihubungi. Periksa Property ID dan kredensial Service Account.';
    }
}
