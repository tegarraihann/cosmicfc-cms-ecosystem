<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\Competition;
use App\Models\CompetitionAudit;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Season;
use App\Services\CompetitionService;
use App\Services\StandingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    public function index(Request $request): Response
    {
        $items = Competition::with(['logo', 'banner', 'seasons' => fn ($q) => $q->with('participants')->withCount('participants')->latest('starts_at')])->when($request->string('search')->toString(), fn ($q, $v) => $q->where(fn ($n) => $n->where('name', 'like', "%{$v}%")->orWhereHas('seasons', fn ($s) => $s->where('name', 'like', "%{$v}%"))))->when($request->string('type')->toString(), fn ($q, $v) => $q->where('type', $v))->when($request->string('status')->toString(), fn ($q, $v) => $q->where('status', $v))->latest()->paginate(15)->withQueryString()->through(fn ($c) => $this->competitionData($c));

        return Inertia::render('Admin/Competitions', ['competitions' => $items, 'filters' => $request->only(['search', 'type', 'status']), 'clubs' => Club::with('logo')->where('status', 'active')->orderBy('display_name')->get()->map(fn ($c) => ['id' => $c->id, 'name' => $c->display_name, 'shortName' => $c->short_name, 'logo' => $c->logo?->url]), 'media' => Media::where('status', 'active')->latest()->get()->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'url' => $m->url]), 'can' => ['create' => $request->user()->hasPermission('competitions.create'), 'update' => $request->user()->hasPermission('competitions.update'), 'participants' => $request->user()->hasPermission('competitions.participants'), 'publish' => $request->user()->hasPermission('competitions.publish'), 'delete' => $request->user()->hasPermission('competitions.delete'), 'browse_media' => $request->user()->hasPermission('media.view'), 'upload_media' => $request->user()->hasPermission('media.upload')]]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/CompetitionCreate', [
            'media' => Media::where('status', 'active')->latest()->get()->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'url' => $m->url]),
            'can' => [
                'browse_media' => $request->user()->hasPermission('media.view'),
                'upload_media' => $request->user()->hasPermission('media.upload'),
            ],
        ]);
    }

    public function createSeason(Request $request): Response
    {
        return Inertia::render('Admin/SeasonCreate', [
            'competitions' => Competition::whereIn('status', ['draft', 'active'])->orderBy('name')->get(['id', 'name', 'short_name', 'status']),
            'selectedCompetitionId' => $request->integer('competition') ?: null,
        ]);
    }

    public function store(Request $request, CompetitionService $service): RedirectResponse
    {
        $d = $this->competitionValidation($request);
        $d['slug'] = $this->uniqueCompetitionSlug($d['name']);
        $competition = DB::transaction(function () use ($d, $request) {
            $c = Competition::create([...$d, 'uuid' => (string) Str::uuid(), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->syncMedia($c, $d);
            CompetitionAudit::create(['competition_id' => $c->id, 'user_id' => $request->user()->id, 'action' => 'created', 'after_payload' => $d]);

            return $c;
        });
        $service->forget();

        if ($request->boolean('continue_to_season')) {
            return redirect()->route('admin.competitions.seasons.create', ['competition' => $competition->id])->with('success', 'Kompetisi berhasil dibuat. Silakan lengkapi data musim.');
        }

        return redirect()->route('admin.competitions.index')->with('success', 'Kompetisi berhasil dibuat.');
    }

    public function update(Request $request, Competition $competition, CompetitionService $service): RedirectResponse
    {
        $d = $this->competitionValidation($request, $competition);
        $d['slug'] = $competition->slug ?: $this->uniqueCompetitionSlug($d['name'], $competition);
        $before = $competition->toArray();
        $competition->update([...$d, 'updated_by' => $request->user()->id]);
        $this->syncMedia($competition, $d);
        CompetitionAudit::create(['competition_id' => $competition->id, 'user_id' => $request->user()->id, 'action' => 'updated', 'before_payload' => $before, 'after_payload' => $d]);
        $service->forget();

        return back()->with('success', 'Kompetisi berhasil diperbarui.');
    }

    public function saveSeason(Request $request, Competition $competition, ?Season $season, CompetitionService $service, StandingsService $standings): RedirectResponse
    {
        $d = $request->validate(['name' => ['required', 'string', 'max:100'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after_or_equal:starts_at'], 'format' => ['required', Rule::in(['league', 'group', 'knockout', 'hybrid', 'friendly'])], 'current_matchday' => ['required', 'integer', 'min:0'], 'total_matchdays' => ['required', 'integer', 'min:0'], 'max_participants' => ['nullable', 'integer', 'between:2,100'], 'win_points' => ['required', 'integer', 'min:0'], 'draw_points' => ['required', 'integer', 'min:0'], 'loss_points' => ['required', 'integer', 'min:0'], 'tie_breakers' => ['array', 'min:1'], 'tie_breakers.*' => [Rule::in(['points', 'goal_difference', 'goals_for', 'head_to_head'])], 'regulations' => ['array'], 'regulations.*.title' => ['required', 'string', 'max:100'], 'regulations.*.body' => ['nullable', 'string', 'max:1000'], 'regulations.*.active' => ['boolean'], 'is_featured' => ['boolean'], 'status' => ['required', Rule::in(['draft', 'registration', 'scheduled', 'ongoing', 'completed', 'cancelled'])]]);
        $d['slug'] = $season?->slug ?: $this->uniqueSeasonSlug($d['name'], $season);
        if ($d['current_matchday'] > $d['total_matchdays']) {
            throw ValidationException::withMessages(['current_matchday' => 'Matchday berjalan tidak boleh melebihi total matchday.']);
        }$data = collect($d)->except(['win_points', 'draw_points', 'loss_points', 'tie_breakers'])->all();
        $data['scoring_rules'] = ['win' => $d['win_points'], 'draw' => $d['draw_points'], 'loss' => $d['loss_points'], 'tie_breakers' => $d['tie_breakers']];
        $data['competition_id'] = $competition->id;
        $data['is_active'] = in_array($d['status'], ['scheduled', 'ongoing']);
        $data['published_at'] = $d['is_featured'] ? now() : null;
        DB::transaction(function () use ($data, $season, $competition, $request, $d) {
            if ($d['is_featured']) {
                Season::where('id', '!=', $season?->id)->update(['is_featured' => false]);
            }$target = $season ?: new Season;
            $before = $target->exists ? $target->toArray() : null;
            $target->fill([...$data, 'created_by' => $target->created_by ?: $request->user()->id, 'updated_by' => $request->user()->id])->save();
            CompetitionAudit::create(['competition_id' => $competition->id, 'season_id' => $target->id, 'user_id' => $request->user()->id, 'action' => $season ? 'season_updated' : 'season_created', 'before_payload' => $before, 'after_payload' => $data]);
        });
        $service->forget();
        $savedSeason = Season::where('slug', $d['slug'])->first();
        if ($savedSeason?->standingSnapshots()->exists()) {
            $standings->recalculate($savedSeason, $request->user()->id, 'season_rules_updated', (bool) $savedSeason->published_at);
        }

        if ($request->boolean('from_create_page')) {
            return redirect()->route('admin.competitions.index')->with('success', 'Musim berhasil dibuat. Tambahkan peserta sebelum membuat pertandingan kompetisi.');
        }

        return back()->with('success', 'Musim berhasil disimpan.');
    }

    public function syncParticipants(Request $request, Season $season, CompetitionService $service): RedirectResponse
    {
        $d = $request->validate([
            'participants' => ['array'],
            'participants.*.club_id' => ['required', 'distinct', 'exists:clubs,id'],
        ]);
        if (in_array($season->status, ['scheduled', 'ongoing', 'completed']) && $season->participants()->exists()) {
            return back()->withErrors(['participants' => 'Peserta terkunci setelah musim dijadwalkan.']);
        }if ($season->max_participants && count($d['participants']) > $season->max_participants) {
            throw ValidationException::withMessages(['participants' => 'Jumlah peserta melebihi kapasitas musim.']);
        }DB::transaction(function () use ($d, $season, $request) {
            $season->participants()->delete();
            foreach ($d['participants'] as $index => $participant) {
                $season->participants()->create([
                    'club_id' => $participant['club_id'],
                    'seed' => null,
                    'group_name' => null,
                    'display_order' => $index + 1,
                    'status' => 'active',
                    'joined_at' => now()->toDateString(),
                ]);
            }CompetitionAudit::create(['competition_id' => $season->competition_id, 'season_id' => $season->id, 'user_id' => $request->user()->id, 'action' => 'participants_synced', 'after_payload' => $d]);
        });
        $service->forget();

        return back()->with('success', 'Peserta musim berhasil diperbarui.');
    }

    public function destroy(Request $request, Competition $competition, CompetitionService $service): RedirectResponse
    {
        if ($competition->seasons()->exists()) {
            return back()->withErrors(['competition' => 'Kompetisi yang mempunyai musim tidak dapat dihapus.']);
        }$competition->mediaUsages()->delete();
        $competition->delete();
        CompetitionAudit::create(['competition_id' => $competition->id, 'user_id' => $request->user()->id, 'action' => 'deleted']);
        $service->forget();

        return back()->with('success', 'Kompetisi berhasil dihapus.');
    }

    private function competitionValidation(Request $r, ?Competition $c = null): array
    {
        return $r->validate(['name' => ['required', 'string', 'max:120'], 'short_name' => ['required', 'string', 'max:20'], 'type' => ['required', Rule::in(['league', 'tournament', 'friendly_series', 'single_event'])], 'organizer' => ['nullable', 'string', 'max:120'], 'description' => ['nullable', 'string', 'max:2000'], 'logo_media_id' => ['nullable', 'exists:media,id'], 'banner_media_id' => ['nullable', 'exists:media,id'], 'website_url' => ['nullable', 'url', 'max:500'], 'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'archived'])]]);
    }

    private function uniqueCompetitionSlug(string $name, ?Competition $competition = null): string
    {
        $base = Str::slug($name) ?: 'kompetisi';
        $slug = $base;
        $suffix = 2;

        while (Competition::where('slug', $slug)->when($competition, fn ($query) => $query->where('id', '!=', $competition->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function uniqueSeasonSlug(string $name, ?Season $season = null): string
    {
        $base = Str::slug($name) ?: 'musim';
        $slug = $base;
        $suffix = 2;

        while (Season::where('slug', $slug)->when($season, fn ($query) => $query->where('id', '!=', $season->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function syncMedia(Competition $c, array $d): void
    {
        $c->mediaUsages()->delete();
        foreach (['logo_media_id', 'banner_media_id'] as $f) {
            if ($d[$f] ?? null) {
                MediaUsage::create(['media_id' => $d[$f], 'usable_type' => Competition::class, 'usable_id' => $c->id, 'field_key' => $f]);
            }
        }
    }

    private function competitionData(Competition $c): array
    {
        return [...$c->only(['id', 'name', 'short_name', 'slug', 'type', 'organizer', 'description', 'logo_media_id', 'banner_media_id', 'website_url', 'status']), 'logo_url' => $c->logo?->url, 'banner_url' => $c->banner?->url, 'seasons' => $c->seasons->map(function ($s) {
            $rules = $s->scoring_rules ?? [];

            return [...$s->only(['id', 'name', 'slug', 'format', 'current_matchday', 'total_matchdays', 'max_participants', 'is_featured', 'status']), 'starts_at' => $s->starts_at?->format('Y-m-d'), 'ends_at' => $s->ends_at?->format('Y-m-d'), 'win_points' => $rules['win'] ?? 3, 'draw_points' => $rules['draw'] ?? 1, 'loss_points' => $rules['loss'] ?? 0, 'tie_breakers' => $rules['tie_breakers'] ?? ['points', 'goal_difference', 'goals_for'], 'regulations' => $s->regulations ?? [], 'participants_count' => $s->participants_count, 'participants' => $s->relationLoaded('participants') ? $s->participants : []];
        })->values()];
    }
}
