<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\MatchEventAudit;
use App\Services\MatchEventService;
use App\Services\MatchService;
use App\Services\PlayerService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MatchEventController extends Controller
{
    public function store(Request $request, GameMatch $match, MatchEventService $events, MatchService $matches): RedirectResponse
    {
        $this->guardPublish($request);
        $data = $this->validated($request, $match, $events);
        $this->guardCompleted($request, $match, $data);
        DB::transaction(function () use ($request, $match, $data, $events) {
            $event = $match->events()->create([...$data, 'uuid' => (string) Str::uuid(), 'is_score_event' => in_array($data['type'], MatchEventService::SCORE_TYPES), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->audit($request, $match, $event, 'created', null, $event->toArray());
            $events->synchronizeScore($match, $request->user()->id, 'event_created');
        });
        $matches->forget();
        app(PlayerService::class)->forget();
        $this->refreshStatistics($match, $request);

        return back()->with('success', 'Event pertandingan berhasil ditambahkan.');
    }

    public function update(Request $request, GameMatch $match, MatchEvent $event, MatchEventService $events, MatchService $matches): RedirectResponse
    {
        abort_unless($event->match_id === $match->id, 404);
        $this->guardPublish($request);
        $data = $this->validated($request, $match, $events);
        $this->guardCompleted($request, $match, $data);
        DB::transaction(function () use ($request, $match, $event, $data, $events) {
            $before = $event->toArray();
            $event->update([...$data, 'is_score_event' => in_array($data['type'], MatchEventService::SCORE_TYPES), 'updated_by' => $request->user()->id]);
            $this->audit($request, $match, $event, in_array($match->status, ['completed', 'walkover']) ? 'corrected' : 'updated', $before, $event->fresh()->toArray());
            $events->synchronizeScore($match, $request->user()->id, 'event_updated');
        });
        $matches->forget();
        app(PlayerService::class)->forget();
        $this->refreshStatistics($match, $request);

        return back()->with('success', 'Event pertandingan berhasil diperbarui.');
    }

    public function destroy(Request $request, GameMatch $match, MatchEvent $event, MatchEventService $events, MatchService $matches): RedirectResponse
    {
        abort_unless($event->match_id === $match->id, 404);
        $this->guardCompleted($request, $match, $request->validate(['correction_reason' => ['nullable', 'string', 'max:500']]));
        DB::transaction(function () use ($request, $match, $event, $events) {
            $before = $event->toArray();
            $event->delete();
            $this->audit($request, $match, $event, 'deleted', $before, null);
            $events->synchronizeScore($match, $request->user()->id, 'event_deleted');
        });
        $matches->forget();
        app(PlayerService::class)->forget();
        $this->refreshStatistics($match, $request);

        return back()->with('success', 'Event pertandingan berhasil dihapus.');
    }

    private function validated(Request $request, GameMatch $match, MatchEventService $service): array
    {
        $data = $request->validate(['club_id' => ['required', 'integer'], 'type' => ['required', Rule::in(MatchEventService::TYPES)], 'period' => ['required', Rule::in(MatchEventService::PERIODS)], 'minute' => ['required', 'integer', 'between:0,150'], 'added_minute' => ['nullable', 'integer', 'between:0,30'], 'primary_player_id' => ['nullable', 'exists:players,id'], 'primary_player_name' => ['nullable', 'string', 'max:150'], 'secondary_player_id' => ['nullable', 'exists:players,id'], 'secondary_player_name' => ['nullable', 'string', 'max:150'], 'note' => ['nullable', 'string', 'max:1000'], 'is_public' => ['boolean'], 'sort_order' => ['nullable', 'integer', 'min:0'], 'correction_reason' => ['nullable', 'string', 'max:500']]);
        $data['added_minute'] ??= 0;
        $data['sort_order'] ??= 0;
        if ($data['type'] === 'own_goal') {
            $data['secondary_player_id'] = null;
            $data['secondary_player_name'] = null;
        }
        $service->validateActors($match, $data);

        return $data;
    }

    private function guardCompleted(Request $request, GameMatch $match, array $data): void
    {
        if (! in_array($match->status, ['completed', 'walkover'])) {
            return;
        }
        abort_unless($request->user()->hasPermission('match-events.correct'), 403, 'Koreksi event pertandingan selesai memerlukan hak khusus.');
        if (blank($data['correction_reason'] ?? null)) {
            throw ValidationException::withMessages(['correction_reason' => 'Alasan koreksi wajib untuk pertandingan selesai.']);
        }
    }

    private function guardPublish(Request $request): void
    {
        if ($request->boolean('is_public')) {
            abort_unless($request->user()->hasPermission('match-events.publish'), 403, 'Publikasi event memerlukan hak khusus.');
        }
    }

    private function audit(Request $request, GameMatch $match, MatchEvent $event, string $action, ?array $before, ?array $after): void
    {
        MatchEventAudit::create(['match_event_id' => $event->id, 'match_id' => $match->id, 'user_id' => $request->user()->id, 'action' => $action, 'before_payload' => $before, 'after_payload' => $after, 'correction_reason' => $request->input('correction_reason'), 'created_at' => now()]);
    }

    private function refreshStatistics(GameMatch $match, Request $request): void
    {
        if ($match->season && in_array($match->status, ['completed', 'walkover'])) {
            app(StandingsService::class)->recalculate($match->season, $request->user()->id, 'match_event_updated', $match->is_public);
        }
    }
}
