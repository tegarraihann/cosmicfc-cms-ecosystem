<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoogleAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $request->validate(['start_date' => ['required', 'date'], 'end_date' => ['required', 'date', 'after_or_equal:start_date']]);
        $analytics->report((string) $request->string('start_date'), (string) $request->string('end_date'), true);

        return back()->with('success', 'Data Google Analytics berhasil diperbarui.');
    }
}
