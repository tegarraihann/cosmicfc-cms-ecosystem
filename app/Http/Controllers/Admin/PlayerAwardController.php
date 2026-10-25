<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwardType;
use App\Models\GameMatch;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Player;
use App\Models\PlayerAward;
use App\Models\PlayerAwardAudit;
use App\Models\Season;
use App\Services\PlayerAwardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlayerAwardController extends Controller
{
    public function index(Request $request, PlayerAwardService $service): Response
    {
        $seasons = Season::with('competition')->latest()->get();
        $season = $seasons->firstWhere('id', $request->integer('season_id'))
            ?? $seasons->firstWhere('is_featured', true)
            ?? $seasons->first();

        $awards = $season
            ? PlayerAward::with(['awardType', 'player.photo', 'primaryMedia', 'match'])
                ->where('season_id', $season->id)
                ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
                ->when($request->integer('award_type_id'), fn ($query, $type) => $query->where('award_type_id', $type))
                ->latest()
                ->get()
                ->map(fn ($award) => $this->adminResource($award))
            : collect();
        $candidates = $season ? $service->candidates($season) : collect();
        $matches = $season
            ? GameMatch::where('season_id', $season->id)
                ->whereIn('status', ['completed', 'walkover'])
                ->with(['homeClub', 'awayClub'])
                ->latest('kickoff_at')
                ->get()
                ->map(fn ($match) => ['id' => $match->id, 'label' => $match->homeClub->display_name.' '.$match->home_score.'–'.$match->away_score.' '.$match->awayClub->display_name])
            : collect();
        Inertia::share('categoryTypes', AwardType::withCount('awards')->orderBy('display_order')->get());

        return Inertia::render('Admin/PlayerAwards', ['awards' => $awards, 'seasons' => $seasons->map(fn ($item) => ['id' => $item->id, 'name' => ($item->competition?->short_name ?? '').' '.$item->name]), 'seasonId' => $season?->id, 'types' => AwardType::where('is_active', true)->orderBy('display_order')->get(), 'candidates' => $candidates, 'matches' => $matches, 'media' => Media::where('status', 'active')->latest()->limit(100)->get()->map(fn ($media) => ['id' => $media->id, 'title' => $media->title, 'url' => $media->url]), 'filters' => $request->only(['status', 'award_type_id']), 'can' => ['create' => $request->user()->hasPermission('player-awards.create'), 'update' => $request->user()->hasPermission('player-awards.update'), 'publish' => $request->user()->hasPermission('player-awards.publish'), 'correct' => $request->user()->hasPermission('player-awards.correct'), 'archive' => $request->user()->hasPermission('player-awards.archive'), 'types' => $request->user()->hasPermission('player-awards.types')]]);
    }

    public function store(Request $request, PlayerAwardService $service): RedirectResponse
    {
        $data = $this->validated($request, $service);
        DB::transaction(function () use ($request, $data) {
            $award = PlayerAward::create([...$data, 'type' => $data['legacy_type'], 'period_month' => $data['period_key'], 'is_published' => $data['status'] === 'published', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->afterSave($request, $award, $data, null);
        });
        $service->forget();

        return back()->with('success', 'Player Award berhasil dibuat.');
    }

    public function update(Request $request, PlayerAward $award, PlayerAwardService $service): RedirectResponse
    {
        if ($award->status === 'published') {
            abort_unless($request->user()->hasPermission('player-awards.correct'), 403);
            if (blank($request->input('correction_reason'))) {
                throw ValidationException::withMessages(['correction_reason' => 'Alasan koreksi wajib untuk award published.']);
            }
        }
        $data = $this->validated($request, $service, $award);
        DB::transaction(function () use ($request, $award, $data) {
            $before = $award->toArray();
            $award->update([...$data, 'type' => $data['legacy_type'], 'period_month' => $data['period_key'], 'is_published' => $data['status'] === 'published', 'updated_by' => $request->user()->id]);
            $this->afterSave($request, $award, $data, $before);
        });
        $service->forget();

        return back()->with('success', 'Player Award berhasil diperbarui.');
    }

    public function archive(Request $request, PlayerAward $award, PlayerAwardService $service): RedirectResponse
    {
        $before = $award->toArray();
        $award->update(['status' => 'archived', 'is_published' => false, 'is_featured' => false, 'updated_by' => $request->user()->id]);
        $this->audit($request, $award, 'archived', $before, $award->fresh()->toArray());
        $service->forget();

        return back()->with('success', 'Player Award berhasil diarsipkan.');
    }

    public function saveType(Request $request): RedirectResponse
    {
        $data = $request->validate(['id' => ['nullable', 'integer', 'exists:award_types,id'], 'name' => ['required', 'string', 'max:100'], 'period_type' => ['required', Rule::in(['match', 'month', 'season', 'custom'])], 'description' => ['nullable', 'string', 'max:500'], 'is_active' => ['boolean']]);
        if ($request->integer('id')) {
            AwardType::findOrFail($request->integer('id'))->update(collect($data)->except('id')->all());
        } else {
            $baseSlug = Str::slug($data['name']) ?: 'kategori-award';
            $slug = $baseSlug;
            $suffix = 2;
            while (AwardType::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }
            AwardType::create([...collect($data)->except('id')->all(), 'slug' => $slug, 'display_order' => ((int) AwardType::max('display_order')) + 10]);
        }

        return back()->with('success', $request->integer('id') ? 'Kategori award berhasil diperbarui.' : 'Kategori award berhasil ditambahkan.');
    }

    private function validated(Request $request, PlayerAwardService $service, ?PlayerAward $award = null): array
    {
        $data = $request->validate(['award_type_id' => ['required', 'exists:award_types,id'], 'player_id' => ['required', 'exists:players,id'], 'season_id' => ['required', 'exists:seasons,id'], 'match_id' => ['nullable', 'exists:matches,id'], 'period_key' => ['nullable', 'string', 'max:30'], 'title' => ['required', 'string', 'max:150'], 'editorial' => ['nullable', 'string', 'max:1500'], 'quote' => ['nullable', 'string', 'max:500'], 'primary_media_id' => ['nullable', 'exists:media,id'], 'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'archived'])], 'is_featured' => ['boolean'], 'published_at' => ['nullable', 'date', Rule::requiredIf(fn () => $request->input('status') === 'scheduled')], 'nominee_ids' => ['array'], 'nominee_ids.*' => ['exists:players,id'], 'correction_reason' => ['nullable', 'string', 'max:500']]);
        $type = AwardType::findOrFail($data['award_type_id']);
        $season = Season::findOrFail($data['season_id']);
        $player = Player::findOrFail($data['player_id']);
        if (! $player->memberships()->where('season_id', $season->id)->exists()) {
            throw ValidationException::withMessages(['player_id' => 'Pemain bukan anggota season terpilih.']);
        }
        if ($type->period_type === 'month' && ! preg_match('/^\d{4}-\d{2}$/', $data['period_key'] ?? '')) {
            throw ValidationException::withMessages(['period_key' => 'Periode bulanan harus berformat YYYY-MM.']);
        }
        if ($type->period_type === 'match') {
            if (! $data['match_id']) {
                throw ValidationException::withMessages(['match_id' => 'Pertandingan wajib untuk award ini.']);
            } $match = GameMatch::find($data['match_id']);
            if ($match->season_id !== $season->id) {
                throw ValidationException::withMessages(['match_id' => 'Pertandingan tidak berasal dari season terpilih.']);
            } if (! $match->lineups()->where('player_id', $player->id)->exists()) {
                throw ValidationException::withMessages(['player_id' => 'Pemain tidak terdaftar pada lineup pertandingan.']);
            }
            $data['period_key'] = 'match-'.$match->id;
        } elseif ($type->period_type === 'season') {
            $data['period_key'] = 'season-'.$season->id;
        } elseif ($type->period_type === 'custom' && blank($data['period_key'])) {
            throw ValidationException::withMessages(['period_key' => 'Periode penilaian wajib diisi.']);
        }
        $duplicate = PlayerAward::where('award_type_id', $type->id)->where('season_id', $season->id)->when($type->period_type === 'match', fn ($q) => $q->where('match_id', $data['match_id']), fn ($q) => $q->where('period_key', $data['period_key']))->when($award, fn ($q) => $q->whereKeyNot($award->id))->where('status', '!=', 'archived')->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['period_key' => 'Award untuk kategori dan periode ini sudah tersedia.']);
        }
        if (in_array($data['status'], ['published', 'scheduled'])) {
            abort_unless($request->user()->hasPermission('player-awards.publish'), 403);
            if (blank($data['editorial'])) {
                throw ValidationException::withMessages(['editorial' => 'Editorial wajib sebelum publikasi.']);
            }
        }
        if ($data['is_featured']) {
            PlayerAward::whereKeyNot($award?->id)->whereHas('awardType', fn ($q) => $q->where('slug', 'player-of-the-month'))->update(['is_featured' => false]);
        }
        $data['statistics_snapshot'] = $service->snapshot($player, $season);
        $data['legacy_type'] = $type->slug === 'player-of-the-month' ? 'player_of_month' : ($type->slug === 'player-of-the-match' ? 'motm' : 'custom');
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : ($data['status'] === 'scheduled' ? $data['published_at'] : null);

        return $data;
    }

    private function afterSave(Request $request, PlayerAward $award, array $data, ?array $before): void
    {
        $award->nominees()->delete();
        foreach (array_unique([...($data['nominee_ids'] ?? []), $award->player_id]) as $index => $playerId) {
            $award->nominees()->create(['player_id' => $playerId, 'statistics_snapshot' => app(PlayerAwardService::class)->snapshot(Player::findOrFail($playerId), $award->season), 'display_order' => $index + 1]);
        }
        MediaUsage::where('usable_type', PlayerAward::class)->where('usable_id', $award->id)->delete();
        if ($award->primary_media_id) {
            MediaUsage::create(['media_id' => $award->primary_media_id, 'usable_type' => PlayerAward::class, 'usable_id' => $award->id, 'field_key' => 'primary_media_id']);
        }
        $this->audit($request, $award, $before ? ($award->status === 'published' ? 'corrected' : 'updated') : 'created', $before, $award->fresh()->toArray());
    }

    private function audit(Request $request, PlayerAward $award, string $action, ?array $before, ?array $after): void
    {
        PlayerAwardAudit::create(['player_award_id' => $award->id, 'user_id' => $request->user()->id, 'action' => $action, 'before_payload' => $before, 'after_payload' => $after, 'correction_reason' => $request->input('correction_reason'), 'created_at' => now()]);
    }

    private function adminResource(PlayerAward $award): array
    {
        return [...$award->toArray(), 'type_name' => $award->awardType?->name, 'player_name' => $award->player->display_name, 'player_image' => $award->primaryMedia?->url ?? $award->player->photo?->url, 'nominee_ids' => $award->nominees()->pluck('player_id'), 'match_label' => $award->match?->slug];
    }
}
