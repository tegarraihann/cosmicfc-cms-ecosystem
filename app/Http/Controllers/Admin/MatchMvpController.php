<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwardType;
use App\Models\GameMatch;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\PlayerAward;
use App\Models\PlayerAwardAudit;
use App\Services\MatchEventService;
use App\Services\PlayerAwardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MatchMvpController extends Controller
{
    public function show(Request $request, GameMatch $match, MatchEventService $events): Response
    {
        $match->load([
            'season.competition', 'homeClub.logo', 'awayClub.logo',
            'lineups.player.photo', 'lineups.club', 'events.club',
            'events.primaryPlayer', 'events.secondaryPlayer',
            'mvpAssessments.player.photo', 'mvpAssessments.evaluator',
            'mvpAward.player.photo', 'mvpAward.nominees', 'mvpAward.primaryMedia',
        ]);

        $latestDataChange = collect([$match->updated_at, $match->events->max('updated_at')])->filter()->max();
        $latestAssessment = $match->mvpAssessments->max('updated_at');

        return Inertia::render('Admin/MatchMvp', [
            'match' => [
                'id' => $match->id,
                'slug' => $match->slug,
                'status' => $match->status,
                'home' => $match->homeClub->display_name,
                'away' => $match->awayClub->display_name,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'kickoff' => $match->kickoff_at?->translatedFormat('d M Y, H.i').' WIB',
                'venue' => $match->venue_name,
                'competition' => $match->season?->competition?->name ?? $this->typeLabel($match->match_type),
                'matchday' => $match->matchday,
                'reconciliation' => $events->reconciliation($match),
                'ready' => in_array($match->status, ['completed', 'walkover']) && $match->lineups->whereNotNull('player_id')->isNotEmpty(),
                'data_changed' => $latestAssessment && $latestDataChange && $latestDataChange->gt($latestAssessment),
            ],
            'candidates' => $match->lineups->whereNotNull('player_id')->unique('player_id')->map(fn ($lineup) => $this->candidateResource($match, $lineup))->values(),
            'assessments' => $match->mvpAssessments->mapWithKeys(fn ($assessment) => [$assessment->player_id => [
                'player_id' => $assessment->player_id,
                'result_impact' => $assessment->result_impact,
                'position_performance' => $assessment->position_performance,
                'consistency' => $assessment->consistency,
                'crucial_moments' => $assessment->crucial_moments,
                'discipline_teamwork' => $assessment->discipline_teamwork,
                'total_score' => (float) $assessment->total_score,
                'assessment_note' => $assessment->assessment_note,
                'evaluator' => $assessment->evaluator?->name,
            ]]),
            'award' => $match->mvpAward ? [
                'id' => $match->mvpAward->id,
                'player_id' => $match->mvpAward->player_id,
                'title' => $match->mvpAward->title,
                'editorial' => $match->mvpAward->editorial,
                'quote' => $match->mvpAward->quote,
                'primary_media_id' => $match->mvpAward->primary_media_id,
                'status' => $match->mvpAward->status,
                'published_at' => $match->mvpAward->published_at?->format('Y-m-d\TH:i'),
                'nominee_ids' => $match->mvpAward->nominees->pluck('player_id'),
            ] : null,
            'media' => Media::where('status', 'active')->latest()->limit(100)->get()->map(fn ($media) => [
                'id' => $media->id, 'title' => $media->title, 'url' => $media->url,
            ]),
            'canPublish' => $request->user()->hasPermission('player-awards.publish'),
        ]);
    }

    public function saveAssessments(Request $request, GameMatch $match): RedirectResponse
    {
        $this->ensureReady($match);
        $data = $request->validate([
            'candidates' => ['required', 'array', 'min:1', 'max:3'],
            'candidates.*.player_id' => ['required', 'integer', 'distinct', 'exists:players,id'],
            'candidates.*.result_impact' => ['required', 'integer', 'between:1,5'],
            'candidates.*.position_performance' => ['required', 'integer', 'between:1,5'],
            'candidates.*.consistency' => ['required', 'integer', 'between:1,5'],
            'candidates.*.crucial_moments' => ['required', 'integer', 'between:1,5'],
            'candidates.*.discipline_teamwork' => ['required', 'integer', 'between:1,5'],
            'candidates.*.assessment_note' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'candidates.required' => 'Pilih minimal satu kandidat MVP.',
            'candidates.min' => 'Pilih minimal satu kandidat MVP.',
            'candidates.max' => 'Kandidat MVP maksimal tiga pemain.',
            'candidates.*.assessment_note.required' => 'Catatan penilaian wajib diisi.',
            'candidates.*.assessment_note.min' => 'Catatan penilaian minimal 10 karakter.',
            'candidates.*.result_impact.between' => 'Nilai dampak pada hasil harus antara 1 sampai 5.',
            'candidates.*.position_performance.between' => 'Nilai performa sesuai posisi harus antara 1 sampai 5.',
            'candidates.*.consistency.between' => 'Nilai konsistensi harus antara 1 sampai 5.',
            'candidates.*.crucial_moments.between' => 'Nilai momen krusial harus antara 1 sampai 5.',
            'candidates.*.discipline_teamwork.between' => 'Nilai disiplin dan kerja sama harus antara 1 sampai 5.',
        ]);

        $eligibleIds = $match->lineups()->whereNotNull('player_id')->pluck('player_id');
        if (collect($data['candidates'])->pluck('player_id')->diff($eligibleIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['candidates' => 'Kandidat MVP harus berasal dari susunan pemain pertandingan.']);
        }

        DB::transaction(function () use ($data, $match, $request) {
            $selectedIds = collect($data['candidates'])->pluck('player_id');
            $match->mvpAssessments()->whereNotIn('player_id', $selectedIds)->delete();

            foreach ($data['candidates'] as $candidate) {
                $match->mvpAssessments()->updateOrCreate(
                    ['player_id' => $candidate['player_id']],
                    [...$candidate, 'evaluator_id' => $request->user()->id, 'total_score' => $this->score($candidate), 'evidence_snapshot' => $this->evidence($match, $candidate['player_id']), 'assessed_at' => now()],
                );
            }
        });

        return back()->with('success', 'Penilaian kandidat MVP berhasil disimpan.');
    }

    public function saveDecision(Request $request, GameMatch $match, PlayerAwardService $awards): RedirectResponse
    {
        $this->ensureReady($match);
        $data = $request->validate([
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'title' => ['required', 'string', 'max:150'],
            'editorial' => ['required', 'string', 'min:20', 'max:1500'],
            'quote' => ['nullable', 'string', 'max:500'],
            'primary_media_id' => ['nullable', 'exists:media,id'],
            'status' => ['required', 'in:draft,published'],
            'decision_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $assessments = $match->mvpAssessments()->orderByDesc('total_score')->get();
        if ($assessments->isEmpty() || ! $assessments->contains('player_id', $data['player_id'])) {
            throw ValidationException::withMessages(['player_id' => 'Pemenang harus berasal dari kandidat yang sudah dinilai.']);
        }
        if ($data['status'] === 'published') {
            abort_unless($request->user()->hasPermission('player-awards.publish'), 403);
        }
        if ($assessments->first()->player_id !== (int) $data['player_id'] && mb_strlen(trim($data['decision_reason'] ?? '')) < 20) {
            throw ValidationException::withMessages(['decision_reason' => 'Jelaskan alasan memilih pemain yang bukan rekomendasi tertinggi, minimal 20 karakter.']);
        }
        $type = AwardType::firstOrCreate(
            ['slug' => 'player-of-the-match'],
            ['name' => 'Player of the Match', 'period_type' => 'match', 'description' => 'Pemain terbaik dalam satu pertandingan.', 'is_active' => true, 'display_order' => 20],
        );
        $winner = $assessments->firstWhere('player_id', (int) $data['player_id']);
        $snapshot = [...($winner->evidence_snapshot ?? []), 'rating' => round((float) $winner->total_score / 10, 1), 'assessmentScore' => (float) $winner->total_score];

        DB::transaction(function () use ($data, $match, $request, $type, $snapshot) {
            $award = PlayerAward::updateOrCreate(
                ['award_type_id' => $type->id, 'match_id' => $match->id],
                ['player_id' => $data['player_id'], 'season_id' => $match->season_id, 'type' => 'motm', 'period_key' => 'match-'.$match->id, 'title' => $data['title'], 'editorial' => $data['editorial'], 'quote' => $data['quote'] ?? null, 'primary_media_id' => $data['primary_media_id'] ?? null, 'statistics_snapshot' => $snapshot, 'goals' => $snapshot['goals'] ?? 0, 'assists' => $snapshot['assists'] ?? 0, 'rating' => $snapshot['rating'], 'status' => $data['status'], 'is_published' => $data['status'] === 'published', 'is_featured' => false, 'published_at' => $data['status'] === 'published' ? now() : null, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id],
            );

            $award->nominees()->delete();
            foreach ($match->mvpAssessments()->orderByDesc('total_score')->get() as $index => $assessment) {
                $award->nominees()->create(['player_id' => $assessment->player_id, 'statistics_snapshot' => $assessment->evidence_snapshot, 'nomination_note' => $assessment->assessment_note, 'display_order' => $index + 1]);
            }
            MediaUsage::where('usable_type', PlayerAward::class)->where('usable_id', $award->id)->delete();
            if ($award->primary_media_id) {
                MediaUsage::create(['media_id' => $award->primary_media_id, 'usable_type' => PlayerAward::class, 'usable_id' => $award->id, 'field_key' => 'primary_media_id']);
            }
            PlayerAwardAudit::create(['player_award_id' => $award->id, 'user_id' => $request->user()->id, 'action' => $award->wasRecentlyCreated ? 'created' : 'updated', 'after_payload' => [...$award->toArray(), 'decision_reason' => $data['decision_reason'] ?? null], 'correction_reason' => $data['decision_reason'] ?? null, 'created_at' => now()]);
        });

        $awards->forget();

        return back()->with('success', $data['status'] === 'published' ? 'MVP berhasil ditetapkan dan dipublikasikan.' : 'Keputusan MVP berhasil disimpan sebagai draft.');
    }

    private function ensureReady(GameMatch $match): void
    {
        if (! in_array($match->status, ['completed', 'walkover'])) {
            throw ValidationException::withMessages(['match' => 'MVP hanya dapat dinilai setelah pertandingan selesai.']);
        }
        if (! $match->lineups()->whereNotNull('player_id')->exists()) {
            throw ValidationException::withMessages(['match' => 'Susunan pemain harus tersedia sebelum menentukan MVP.']);
        }
    }

    private function score(array $candidate): float
    {
        return round(($candidate['result_impact'] * 30 + $candidate['position_performance'] * 25 + $candidate['consistency'] * 20 + $candidate['crucial_moments'] * 15 + $candidate['discipline_teamwork'] * 10) / 5, 2);
    }

    private function evidence(GameMatch $match, int $playerId): array
    {
        $events = $match->events()->where(fn ($query) => $query->where('primary_player_id', $playerId)->orWhere('secondary_player_id', $playerId))->get();

        return [
            'goals' => $events->where('primary_player_id', $playerId)->whereIn('type', ['goal', 'penalty_goal'])->count(),
            'assists' => $events->where('secondary_player_id', $playerId)->whereIn('type', ['goal', 'penalty_goal'])->count(),
            'yellowCards' => $events->where('primary_player_id', $playerId)->whereIn('type', ['yellow_card', 'second_yellow'])->count(),
            'redCards' => $events->where('primary_player_id', $playerId)->whereIn('type', ['red_card', 'second_yellow'])->count(),
            'events' => $events->map(fn ($event) => ['type' => $event->type, 'minute' => $event->minute, 'addedMinute' => $event->added_minute])->values()->all(),
            'captain' => (bool) $match->lineups()->where('player_id', $playerId)->value('is_captain'),
        ];
    }

    private function candidateResource(GameMatch $match, $lineup): array
    {
        $evidence = $this->evidence($match, $lineup->player_id);

        return [
            'id' => $lineup->player_id,
            'name' => $lineup->player->display_name,
            'number' => $lineup->shirt_number,
            'position' => $lineup->position,
            'role' => $lineup->role,
            'captain' => (bool) $lineup->is_captain,
            'club' => $lineup->club->display_name,
            'image' => $lineup->player->photo?->url,
            'evidence' => $evidence,
        ];
    }

    private function typeLabel(string $type): string
    {
        return ['competitive' => 'Kompetisi', 'friendly' => 'Friendly', 'sparing' => 'Sparing', 'tournament' => 'Turnamen', 'exhibition' => 'Ekshibisi'][$type] ?? ucfirst($type);
    }
}
