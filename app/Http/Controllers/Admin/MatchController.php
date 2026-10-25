<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\CompetitionParticipant;
use App\Models\GameMatch;
use App\Models\MatchAudit;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Player;
use App\Models\Season;
use App\Services\MatchEventService;
use App\Services\MatchService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function index(Request $r): Response
    {
        $q = GameMatch::with(['season.competition', 'homeClub.logo', 'awayClub.logo', 'cover', 'lineups', 'events.primaryPlayer', 'events.secondaryPlayer', 'events.club', 'mvpAward.player'])->when($r->string('search')->toString(), fn ($q, $v) => $q->where(fn ($n) => $n->where('slug', 'like', "%{$v}%")->orWhere('venue_name', 'like', "%{$v}%")->orWhereHas('homeClub', fn ($c) => $c->where('display_name', 'like', "%{$v}%"))->orWhereHas('awayClub', fn ($c) => $c->where('display_name', 'like', "%{$v}%"))))->when($r->string('status')->toString(), fn ($q, $v) => $q->where('status', $v))->when($r->string('match_type')->toString(), fn ($q, $v) => $q->where('match_type', $v))->when($r->integer('season_id'), fn ($q, $v) => $q->where('season_id', $v))->when($r->string('month')->toString(), fn ($q, $v) => $q->whereYear('kickoff_at', substr($v, 0, 4))->whereMonth('kickoff_at', substr($v, 5, 2)))->orderByDesc('kickoff_at');

        return Inertia::render('Admin/Matches', ['matches' => $q->paginate(20)->withQueryString()->through(fn ($m) => $this->resource($m)), 'filters' => $r->only(['search', 'status', 'match_type', 'season_id', 'month']), 'clubs' => Club::where('status', 'active')->orderBy('display_name')->get(['id', 'display_name', 'short_name', 'is_cosmic']), 'seasons' => Season::with('competition')->whereNotNull('competition_id')->latest()->get()->map(fn ($s) => ['id' => $s->id, 'name' => ($s->competition?->short_name ?? '').' '.$s->name, 'status' => $s->status]), 'players' => Player::where('status', 'active')->orderBy('display_name')->get(['id', 'display_name']), 'media' => Media::where('status', 'active')->latest()->get()->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'url' => $m->url]), 'can' => ['create' => $r->user()->hasPermission('matches.create'), 'update' => $r->user()->hasPermission('matches.update'), 'results' => $r->user()->hasPermission('matches.results'), 'lineups' => $r->user()->hasPermission('matches.lineups'), 'publish' => $r->user()->hasPermission('matches.publish'), 'delete' => $r->user()->hasPermission('matches.delete'), 'mvp' => $r->user()->hasPermission('player-awards.view'), 'eventsView' => $r->user()->hasPermission('match-events.view'), 'eventsCreate' => $r->user()->hasPermission('match-events.create'), 'eventsUpdate' => $r->user()->hasPermission('match-events.update'), 'eventsDelete' => $r->user()->hasPermission('match-events.delete'), 'eventsCorrect' => $r->user()->hasPermission('match-events.correct')]]);
    }

    public function store(Request $r, MatchService $s, StandingsService $standings, MatchEventService $events): RedirectResponse
    {
        $d = $this->validated($r);
        DB::transaction(function () use ($d, $r, $events) {
            $m = GameMatch::create([...$d, 'uuid' => (string) Str::uuid(), 'created_by' => $r->user()->id, 'updated_by' => $r->user()->id]);
            $this->afterSave($m, $d, $r, null);
            $events->synchronizeScore($m, $r->user()->id, 'match_created');
        });
        $s->forget();
        $created = GameMatch::where('slug', $d['slug'])->first();
        if ($created?->season && in_array($created->status, ['completed', 'walkover'])) {
            $standings->recalculate($created->season, $r->user()->id, 'match_created', $created->is_public);
        }

        return back()->with('success', 'Pertandingan berhasil dibuat.');
    }

    public function update(Request $r, GameMatch $match, MatchService $s, StandingsService $standings, MatchEventService $events): RedirectResponse
    {
        $wasCounted = in_array($match->status, ['completed', 'walkover']) && $match->count_for_standings;
        $d = $this->validated($r, $match);
        DB::transaction(function () use ($d, $r, $match, $events) {
            $before = $match->toArray();
            $match->update([...$d, 'updated_by' => $r->user()->id]);
            $this->afterSave($match, $d, $r, $before);
            $events->synchronizeScore($match, $r->user()->id, 'score_mode_updated');
        });
        $s->forget();
        if ($match->season && ($wasCounted || in_array($match->status, ['completed', 'walkover']))) {
            $standings->recalculate($match->season, $r->user()->id, 'result_updated', $match->is_public);
        }

        return back()->with('success', 'Pertandingan berhasil diperbarui.');
    }

    public function syncLineups(Request $r, GameMatch $match, MatchService $s): RedirectResponse
    {
        $d = $r->validate(['lineups' => ['array'], 'lineups.*.club_id' => ['required', Rule::in([$match->home_club_id, $match->away_club_id])], 'lineups.*.player_id' => ['nullable', 'exists:players,id'], 'lineups.*.guest_name' => ['nullable', 'string', 'max:100'], 'lineups.*.role' => ['required', Rule::in(['starter', 'substitute'])], 'lineups.*.position' => ['nullable', 'string', 'max:40'], 'lineups.*.shirt_number' => ['nullable', 'integer', 'between:1,99'], 'lineups.*.is_captain' => ['boolean'], 'lineups.*.display_order' => ['integer', 'min:0']]);
        foreach (collect($d['lineups'])->groupBy('club_id') as $rows) {
            if ($rows->where('is_captain', true)->count() > 1) {
                throw ValidationException::withMessages(['lineups' => 'Maksimal satu kapten per klub.']);
            }
            if ($rows->contains(fn ($row) => empty($row['player_id']) && blank($row['guest_name'] ?? null))) {
                throw ValidationException::withMessages(['lineups' => 'Nama pemain tamu wajib diisi jika pemain tidak terdaftar.']);
            }
            $playerIds = $rows->pluck('player_id')->filter();
            if ($playerIds->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['lineups' => 'Pemain yang sama tidak boleh dimasukkan dua kali dalam satu klub.']);
            }
            $guestNames = $rows->filter(fn ($row) => empty($row['player_id']))->map(fn ($row) => mb_strtolower(trim($row['guest_name'])));
            if ($guestNames->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['lineups' => 'Nama pemain tamu yang sama tidak boleh dimasukkan dua kali dalam satu klub.']);
            }
            $shirtNumbers = $rows->pluck('shirt_number')->filter(fn ($number) => $number !== null);
            if ($shirtNumbers->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['lineups' => 'Nomor punggung tidak boleh sama dalam satu klub.']);
            }
        }DB::transaction(function () use ($d, $r, $match) {
            $match->lineups()->delete();
            foreach ($d['lineups'] as $row) {
                $match->lineups()->create($row);
            }MatchAudit::create(['match_id' => $match->id, 'user_id' => $r->user()->id, 'action' => 'lineups_updated', 'after_payload' => $d]);
        });
        $s->forget();

        return back()->with('success', 'Lineup berhasil disimpan.');
    }

    public function destroy(Request $r, GameMatch $match, MatchService $s): RedirectResponse
    {
        if (in_array($match->status, ['completed', 'walkover']) || $match->lineups()->exists() || $match->events()->exists()) {
            return back()->withErrors(['match' => 'Pertandingan selesai atau memiliki lineup/event tidak dapat dihapus.']);
        }$match->mediaUsages()->delete();
        $match->delete();
        MatchAudit::create(['match_id' => $match->id, 'user_id' => $r->user()->id, 'action' => 'deleted']);
        $s->forget();

        return back()->with('success', 'Pertandingan berhasil dihapus.');
    }

    private function validated(Request $r, ?GameMatch $m = null): array
    {
        $r->merge(['score_mode' => $r->input('score_mode', $m?->score_mode ?? 'automatic')]);
        $d = $r->validate(['slug' => ['required', 'alpha_dash', 'max:180', Rule::unique('matches', 'slug')->ignore($m)], 'season_id' => ['nullable', 'exists:seasons,id'], 'match_type' => ['required', Rule::in(['competitive', 'friendly', 'sparing', 'tournament', 'exhibition'])], 'matchday' => ['nullable', 'integer', 'min:1'], 'round_name' => ['nullable', 'string', 'max:100'], 'home_club_id' => ['required', 'exists:clubs,id', 'different:away_club_id'], 'away_club_id' => ['required', 'exists:clubs,id'], 'kickoff_at' => ['nullable', 'date'], 'timezone' => ['required', Rule::in(['Asia/Jakarta'])], 'venue_name' => ['nullable', 'string', 'max:180'], 'venue_address' => ['nullable', 'string', 'max:300'], 'status' => ['required', Rule::in(['draft', 'scheduled', 'live', 'completed', 'postponed', 'cancelled', 'walkover'])], 'score_mode' => ['required', Rule::in(['automatic', 'manual'])], 'home_score' => ['nullable', 'integer', 'min:0'], 'away_score' => ['nullable', 'integer', 'min:0'], 'home_penalty_score' => ['nullable', 'integer', 'min:0'], 'away_penalty_score' => ['nullable', 'integer', 'min:0'], 'manual_score_reason' => ['nullable', 'string', 'max:1000'], 'count_for_standings' => ['boolean'], 'is_featured' => ['boolean'], 'is_public' => ['boolean'], 'cover_media_id' => ['nullable', 'exists:media,id'], 'short_report' => ['nullable', 'string', 'max:3000'], 'attendance' => ['nullable', 'integer', 'min:0']]);

        if ($d['status'] === 'walkover' && $d['score_mode'] !== 'manual') {
            throw ValidationException::withMessages(['score_mode' => 'Skor pertandingan menang WO harus menggunakan mode manual.']);
        }
        if ($d['score_mode'] === 'manual' && mb_strlen(trim((string) ($d['manual_score_reason'] ?? ''))) < 10) {
            throw ValidationException::withMessages(['manual_score_reason' => 'Jelaskan alasan penggunaan skor manual minimal 10 karakter.']);
        }
        if ($d['score_mode'] === 'automatic') {
            $score = $m ? app(MatchEventService::class)->score($m) : ['home' => 0, 'away' => 0, 'homePenalties' => 0, 'awayPenalties' => 0];
            $hasStarted = in_array($d['status'], ['live', 'completed']);
            $d['home_score'] = $hasStarted ? $score['home'] : null;
            $d['away_score'] = $hasStarted ? $score['away'] : null;
            $d['home_penalty_score'] = $score['homePenalties'] ?: null;
            $d['away_penalty_score'] = $score['awayPenalties'] ?: null;
            $d['manual_score_reason'] = null;
        }
        if (in_array($d['status'], ['completed', 'walkover']) && ($d['home_score'] === null || $d['away_score'] === null)) {
            throw ValidationException::withMessages(['home_score' => 'Skor wajib untuk pertandingan selesai.']);
        }if ($d['match_type'] === 'competitive') {
            if (! $d['season_id']) {
                throw ValidationException::withMessages(['season_id' => 'Musim wajib untuk pertandingan kompetitif.']);
            }$ids = CompetitionParticipant::where('season_id', $d['season_id'])->where('status', 'active')->pluck('club_id');
            if (! $ids->contains($d['home_club_id']) || ! $ids->contains($d['away_club_id'])) {
                throw ValidationException::withMessages(['home_club_id' => 'Kedua klub harus menjadi peserta aktif musim.']);
            }
        }

        if ($d['match_type'] === 'sparing') {
            $d['count_for_standings'] = false;
            $d['matchday'] = null;
            $d['round_name'] = null;
        }

        if (! in_array($d['status'], ['scheduled', 'postponed'])) {
            $d['is_featured'] = false;
        }

        return $d;
    }

    private function afterSave(GameMatch $m, array $d, Request $r, ?array $before): void
    {
        if ($d['is_featured'] && in_array($d['status'], ['scheduled', 'postponed'])) {
            GameMatch::whereKeyNot($m->id)->where('is_featured', true)->update(['is_featured' => false]);
        }$m->updateQuietly(['published_at' => $d['is_public'] ? ($m->published_at ?? now()) : null]);
        $m->mediaUsages()->delete();
        if ($d['cover_media_id'] ?? null) {
            MediaUsage::create(['media_id' => $d['cover_media_id'], 'usable_type' => GameMatch::class, 'usable_id' => $m->id, 'field_key' => 'cover_media_id']);
        }MatchAudit::create(['match_id' => $m->id, 'user_id' => $r->user()->id, 'action' => $before ? 'updated' : 'created', 'before_payload' => $before, 'after_payload' => $d]);
    }

    private function resource(GameMatch $m): array
    {
        $eventService = app(MatchEventService::class);

        return [...$m->only(['id', 'slug', 'season_id', 'match_type', 'matchday', 'round_name', 'home_club_id', 'away_club_id', 'timezone', 'venue_name', 'venue_address', 'status', 'score_mode', 'home_score', 'away_score', 'home_penalty_score', 'away_penalty_score', 'manual_score_reason', 'count_for_standings', 'is_featured', 'is_public', 'cover_media_id', 'short_report', 'attendance']), 'kickoff_at' => $m->kickoff_at?->format('Y-m-d\TH:i'), 'home' => $m->homeClub->display_name, 'away' => $m->awayClub->display_name, 'competition' => $m->season?->competition?->name, 'cover_url' => $m->cover?->url, 'lineups' => $m->lineups, 'events' => $m->events->map(fn ($event) => $eventService->resource($event)), 'reconciliation' => $eventService->reconciliation($m), 'mvp' => $m->mvpAward ? ['player' => $m->mvpAward->player?->display_name, 'status' => $m->mvpAward->status] : null];
    }
}
