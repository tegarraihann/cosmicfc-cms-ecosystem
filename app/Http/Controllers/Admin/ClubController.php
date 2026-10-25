<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubAlias;
use App\Models\ClubAudit;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Services\ClubService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClubController extends Controller
{
    public function index(Request $request): Response
    {
        $clubs = Club::with(['logo', 'aliases'])->when($request->string('search')->toString(), function ($query, $search) {
            $query->where(fn ($nested) => $nested->where('display_name', 'like', "%{$search}%")->orWhere('official_name', 'like', "%{$search}%")->orWhere('short_name', 'like', "%{$search}%")->orWhereHas('aliases', fn ($alias) => $alias->where('alias', 'like', "%{$search}%")));
        })->when($request->string('type')->toString(), fn ($query, $type) => $query->where('type', $type))->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))->orderByDesc('is_cosmic')->orderBy('display_name')->paginate(20)->withQueryString()->through(fn ($club) => $this->formData($club));

        return Inertia::render('Admin/Clubs', ['clubs' => $clubs, 'filters' => $request->only(['search', 'type', 'status']), 'media' => Media::where('status', 'active')->latest()->get()->map(fn ($item) => ['id' => $item->id, 'title' => $item->title, 'url' => $item->url]), 'can' => ['create' => $request->user()->hasPermission('clubs.create'), 'update' => $request->user()->hasPermission('clubs.update'), 'archive' => $request->user()->hasPermission('clubs.archive'), 'delete' => $request->user()->hasPermission('clubs.delete'), 'browse_media' => $request->user()->hasPermission('media.view'), 'upload_media' => $request->user()->hasPermission('media.upload')]]);
    }

    public function store(Request $request, ClubService $service): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['display_name']);
        DB::transaction(function () use ($data, $request) {
            $this->ensureRules($data);
            $club = Club::create([...$this->payload($data), 'uuid' => (string) Str::uuid(), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->syncAliases($club, $data['aliases'] ?? []);
            $this->syncMedia($club, $data);
            ClubAudit::create(['club_id' => $club->id, 'user_id' => $request->user()->id, 'action' => 'created', 'after_payload' => $data]);
        });
        $service->forget();

        return back()->with('success', 'Klub berhasil ditambahkan.');
    }

    public function update(Request $request, Club $club, ClubService $service): RedirectResponse
    {
        $data = $this->validated($request, $club);
        $data['slug'] = $club->slug ?: $this->uniqueSlug($data['display_name'], $club);
        DB::transaction(function () use ($data, $request, $club) {
            $this->ensureRules($data, $club);
            $before = $this->formData($club->load('aliases'));
            $club->update([...$this->payload($data), 'updated_by' => $request->user()->id]);
            $this->syncAliases($club, $data['aliases'] ?? []);
            $this->syncMedia($club, $data);
            ClubAudit::create(['club_id' => $club->id, 'user_id' => $request->user()->id, 'action' => 'updated', 'before_payload' => $before, 'after_payload' => $data]);
        });
        $service->forget();

        return back()->with('success', 'Data klub berhasil diperbarui.');
    }

    public function archive(Request $request, Club $club, ClubService $service): RedirectResponse
    {
        if ($club->is_cosmic) {
            return back()->withErrors(['club' => 'Klub internal COSMIC tidak dapat diarsipkan.']);
        }
        $before = $club->status;
        $club->update(['status' => 'archived', 'updated_by' => $request->user()->id]);
        ClubAudit::create(['club_id' => $club->id, 'user_id' => $request->user()->id, 'action' => 'archived', 'before_payload' => ['status' => $before], 'after_payload' => ['status' => 'archived']]);
        $service->forget();

        return back()->with('success', 'Klub berhasil diarsipkan.');
    }

    public function destroy(Request $request, Club $club, ClubService $service): RedirectResponse
    {
        if ($club->is_cosmic || $this->hasUsage($club)) {
            return back()->withErrors(['club' => 'Klub internal atau klub yang sudah digunakan tidak dapat dihapus. Gunakan status archived.']);
        }
        $club->mediaUsages()->delete();
        $club->delete();
        ClubAudit::create(['club_id' => $club->id, 'user_id' => $request->user()->id, 'action' => 'deleted']);
        $service->forget();

        return back()->with('success', 'Klub berhasil dihapus.');
    }

    private function validated(Request $request, ?Club $club = null): array
    {
        return $request->validate(['official_name' => ['required', 'string', 'max:160'], 'display_name' => ['required', 'string', 'max:100'], 'short_name' => ['required', 'string', 'min:2', 'max:12', 'regex:/^[A-Za-z0-9]+$/'], 'type' => ['required', Rule::in(['internal', 'opponent', 'community', 'guest'])], 'city' => ['nullable', 'string', 'max:100'], 'country' => ['required', 'string', 'max:100'], 'founded_year' => ['nullable', 'integer', 'min:1800', 'max:'.now()->year], 'primary_media_id' => ['nullable', 'exists:media,id'], 'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'website_url' => ['nullable', 'url', 'max:500'], 'instagram_url' => ['nullable', 'url', 'max:500'], 'contact_email' => ['nullable', 'email', 'max:160'], 'default_venue' => ['nullable', 'string', 'max:180'], 'is_cosmic' => ['boolean'], 'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'archived'])], 'aliases' => ['array', 'max:10'], 'aliases.*' => ['nullable', 'string', 'max:160']]);
    }

    private function ensureRules(array $data, ?Club $club = null): void
    {
        if (($data['is_cosmic'] ?? false) && Club::where('is_cosmic', true)->when($club, fn ($q) => $q->whereKeyNot($club->id))->exists()) {
            throw ValidationException::withMessages(['is_cosmic' => 'Hanya satu klub yang dapat ditetapkan sebagai COSMIC internal.']);
        }
        $values = collect([$data['official_name'], $data['display_name'], $data['short_name'], ...($data['aliases'] ?? [])])->filter()->map(fn ($value) => ClubService::normalize($value))->unique();
        $duplicate = ClubAlias::whereIn('normalized_alias', $values)->when($club, fn ($q) => $q->where('club_id', '!=', $club->id))->first();
        if ($duplicate) {
            throw ValidationException::withMessages(['aliases' => "Nama atau alias '{$duplicate->alias}' sudah digunakan klub lain."]);
        }
    }

    private function payload(array $data): array
    {
        return [...collect($data)->only(['official_name', 'display_name', 'short_name', 'slug', 'type', 'city', 'country', 'founded_year', 'primary_media_id', 'primary_color', 'secondary_color', 'website_url', 'instagram_url', 'contact_email', 'default_venue', 'is_cosmic', 'status'])->all(), 'alternate_media_id' => null];
    }

    private function uniqueSlug(string $name, ?Club $club = null): string
    {
        $base = Str::slug($name) ?: 'klub';
        $slug = $base;
        $suffix = 2;

        while (Club::where('slug', $slug)->when($club, fn ($query) => $query->where('id', '!=', $club->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function syncAliases(Club $club, array $aliases): void
    {
        $aliases = collect([$club->official_name, $club->display_name, $club->short_name, ...$aliases])->filter()->unique(fn ($value) => ClubService::normalize($value));
        $club->aliases()->delete();
        foreach ($aliases as $alias) {
            $club->aliases()->create(['alias' => $alias, 'normalized_alias' => ClubService::normalize($alias), 'source' => 'manual']);
        }
    }

    private function syncMedia(Club $club, array $data): void
    {
        $club->mediaUsages()->delete();
        foreach (['primary_media_id'] as $field) {
            if ($data[$field] ?? null) {
                MediaUsage::create(['media_id' => $data[$field], 'usable_type' => Club::class, 'usable_id' => $club->id, 'field_key' => $field]);
            }
        }
    }

    private function hasUsage(Club $club): bool
    {
        if (Schema::hasTable('matches') && Schema::hasColumns('matches', ['home_club_id', 'away_club_id']) && (DB::table('matches')->where('home_club_id', $club->id)->orWhere('away_club_id', $club->id)->exists())) {
            return true;
        }
        if (Schema::hasTable('competition_participants') && Schema::hasColumn('competition_participants', 'club_id') && DB::table('competition_participants')->where('club_id', $club->id)->exists()) {
            return true;
        }

        return false;
    }

    private function formData(Club $club): array
    {
        return [...$club->only(['id', 'official_name', 'display_name', 'short_name', 'slug', 'type', 'city', 'country', 'founded_year', 'primary_media_id', 'primary_color', 'secondary_color', 'website_url', 'instagram_url', 'contact_email', 'default_venue', 'is_cosmic', 'status']), 'logo_url' => $club->logo?->url, 'aliases' => $club->aliases->pluck('alias')->reject(fn ($alias) => in_array($alias, [$club->official_name, $club->display_name, $club->short_name]))->values()];
    }
}
