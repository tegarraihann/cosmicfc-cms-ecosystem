<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Season;
use App\Models\StandingAdjustment;
use App\Models\StandingSnapshot;
use App\Models\StatisticRecalculationLog;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StandingsController extends Controller
{
    public function index(Request $request, StandingsService $service): Response
    {
        $seasons = Season::with('competition')->whereNotNull('competition_id')->latest()->get();
        $season = $seasons->firstWhere('id', $request->integer('season_id')) ?? $seasons->firstWhere('is_featured', true) ?? $seasons->first();
        $matchday = $season ? (StandingSnapshot::where('season_id', $season->id)->max('matchday') ?: 1) : 1;
        $rows = $season ? StandingSnapshot::with('club.logo')->where('season_id', $season->id)->where('matchday', $matchday)->orderBy('position')->get()->map(fn ($row) => $service->resource($row)) : collect();

        return Inertia::render('Admin/Standings', ['seasons' => $seasons->map(fn ($item) => ['id' => $item->id, 'name' => ($item->competition?->short_name ?? '').' '.$item->name]), 'seasonId' => $season?->id, 'matchday' => $matchday, 'standings' => $rows, 'adjustments' => $season?->standingAdjustments()->with(['club', 'createdBy:id,name'])->latest()->get() ?? [], 'logs' => $season ? StatisticRecalculationLog::with('requestedBy:id,name')->where('season_id', $season->id)->latest()->limit(10)->get() : [], 'clubs' => $season?->participants()->with('club')->where('status', 'active')->get()->map(fn ($p) => ['id' => $p->club_id, 'name' => $p->club->display_name]) ?? [], 'can' => ['recalculate' => $request->user()->hasPermission('standings.recalculate'), 'publish' => $request->user()->hasPermission('standings.publish'), 'adjust' => $request->user()->hasPermission('standings.adjust'), 'export' => $request->user()->hasPermission('standings.export')]]);
    }

    public function recalculate(Request $request, Season $season, StandingsService $service): RedirectResponse
    {
        $publish = $request->boolean('publish');
        if ($publish) {
            abort_unless($request->user()->hasPermission('standings.publish'), 403);
        }
        $service->recalculate($season, $request->user()->id, $publish ? 'publish' : 'manual', $publish);

        return back()->with('success', $publish ? 'Klasemen berhasil dihitung dan dipublikasikan.' : 'Klasemen berhasil dihitung ulang.');
    }

    public function storeAdjustment(Request $request, Season $season, StandingsService $service): RedirectResponse
    {
        $data = $request->validate(['club_id' => ['required', 'exists:clubs,id'], 'points' => ['required', 'integer', 'between:-100,100', 'not_in:0'], 'reason' => ['required', 'string', 'max:255'], 'effective_matchday' => ['required', 'integer', 'min:1']]);
        abort_unless($season->participants()->where('club_id', $data['club_id'])->exists(), 422, 'Klub bukan peserta musim.');
        $adjustment = StandingAdjustment::create([...$data, 'season_id' => $season->id, 'is_active' => true, 'created_by' => $request->user()->id]);

        try {
            $service->recalculate($season, $request->user()->id, 'adjustment_created', true);
        } catch (\Throwable $exception) {
            $adjustment->update(['is_active' => false]);
            throw $exception;
        }

        return back()->with('success', 'Penyesuaian poin diterapkan dan klasemen publik diperbarui.');
    }

    public function export(Season $season, StandingsService $service): StreamedResponse
    {
        $matchday = StandingSnapshot::where('season_id', $season->id)->max('matchday');
        $rows = StandingSnapshot::with('club')->where('season_id', $season->id)->where('matchday', $matchday)->orderBy('position')->get();

        return ResponseFacade::streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Pos', 'Klub', 'Main', 'M', 'S', 'K', 'GM', 'GK', 'SG', 'Poin']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->position, $row->club->display_name, $row->played, $row->won, $row->drawn, $row->lost, $row->goals_for, $row->goals_against, $row->goal_difference, $row->points]);
            } fclose($out);
        }, 'klasemen-'.$season->slug.'.csv', ['Content-Type' => 'text/csv']);
    }
}
