<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(Request $request, GoogleAnalyticsService $analytics): Response
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['7', '30', '90', 'custom'])],
            'start_date' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:'.today()->subYear()->toDateString(), 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'required_if:period,custom', 'before_or_equal:today'],
        ]);
        $period = $validated['period'] ?? '30';
        $start = $period === 'custom' ? $validated['start_date'] : today()->subDays(((int) $period) - 1)->toDateString();
        $end = $period === 'custom' ? $validated['end_date'] : today()->toDateString();

        return Inertia::render('Admin/Analytics', [
            'analytics' => $analytics->report($start, $end),
            'filters' => ['period' => $period, 'start_date' => $start, 'end_date' => $end],
            'canRefresh' => $request->user()->hasPermission('analytics.refresh'),
        ]);
    }

    public function refresh(Request $request, GoogleAnalyticsService $analytics): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $rateLimitKey = 'analytics-refresh:'.$request->user()->getAuthIdentifier();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 1)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($rateLimitKey) / 60));

            return redirect()->route('admin.analytics.index', [
                'period' => 'custom',
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ])->withErrors([
                'refresh' => "Data baru saja diperbarui. Coba kembali dalam {$minutes} menit.",
            ]);
        }

        RateLimiter::hit($rateLimitKey, 300);
        $analytics->report($validated['start_date'], $validated['end_date'], true);

        return redirect()->route('admin.analytics.index', [
            'period' => 'custom',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ])->with('success', 'Data Google Analytics berhasil diperbarui.');
    }
}
