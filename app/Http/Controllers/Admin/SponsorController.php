<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\PartnerAudit;
use App\Models\Partnership;
use App\Models\Season;
use App\Models\Sponsor;
use App\Services\SponsorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SponsorController extends Controller
{
    public function index(Request $request): Response
    {
        $allSponsors = Sponsor::with(['partnerships.placements'])->get();
        $query = Sponsor::with(['logo', 'partnerships' => fn ($q) => $q->with(['season', 'placements'])->latest('starts_at')])
            ->when($request->string('search')->toString(), fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
            ->when($request->string('category')->toString(), fn ($q, $v) => $q->where('category', $v))
            ->when($request->string('status')->toString(), fn ($q, $v) => $q->where('status', $v))
            ->orderBy('sort_order')->orderBy('name');

        return Inertia::render('Admin/Sponsors', [
            'sponsors' => $query->get()->map(fn ($sponsor) => [
                ...$sponsor->toArray(), 'logo_url' => $sponsor->logo?->url ?? $sponsor->logo_path,
                'partnerships' => $sponsor->partnerships->map(fn ($p) => [...$p->toArray(), 'starts_at' => $p->starts_at?->format('Y-m-d'), 'ends_at' => $p->ends_at?->format('Y-m-d'), 'season_name' => $p->season?->name, 'placement_keys' => $p->placements->where('is_visible', true)->pluck('placement_key')->values()]),
            ]),
            'seasons' => Season::latest('starts_at')->get(['id', 'name']),
            'media' => Media::where('status', 'active')->where('mime_type', 'like', 'image/%')->latest()->limit(200)->get()->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'folder' => $m->folder, 'url' => $m->url]),
            'stats' => [
                'total' => $allSponsors->count(),
                'active' => $allSponsors->where('status', 'active')->count(),
                'expiring' => $allSponsors->flatMap->partnerships->filter(fn ($partnership) => $partnership->status === 'active' && $partnership->ends_at && $partnership->ends_at->between(today(), today()->addDays(30)))->count(),
                'placement_locations' => 1,
            ],
            'filters' => $request->only(['search', 'category', 'status']),
            'can' => [
                ...collect(['create', 'update', 'publish', 'renew', 'archive', 'delete'])->mapWithKeys(fn ($action) => [$action => $request->user()->hasPermission("sponsors.{$action}")])->all(),
                'browse_media' => $request->user()->hasPermission('media.view'),
                'upload_media' => $request->user()->hasPermission('media.upload'),
            ],
        ]);
    }

    public function store(Request $request, SponsorService $service): RedirectResponse
    {
        $data = [
            ...$this->validatedSponsor($request),
            'slug' => $this->uniqueSlug($request->string('name')->toString()),
            'sort_order' => ((int) Sponsor::max('sort_order')) + 10,
        ];
        DB::transaction(function () use ($request, $data) {
            $sponsor = Sponsor::create([...$data, 'is_active' => $data['status'] === 'active', 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->syncMedia($sponsor);
            $this->audit($request, $sponsor, null, 'created', null, $sponsor->toArray());
        });
        $service->forget();

        return back()->with('success', 'Sponsor berhasil ditambahkan.');
    }

    public function update(Request $request, Sponsor $sponsor, SponsorService $service): RedirectResponse
    {
        $before = $sponsor->toArray();
        $data = $this->validatedSponsor($request, $sponsor);
        $sponsor->update([...$data, 'is_active' => $data['status'] === 'active', 'updated_by' => $request->user()->id]);
        $this->syncMedia($sponsor);
        $this->audit($request, $sponsor, null, 'updated', $before, $sponsor->fresh()->toArray());
        $service->forget();

        return back()->with('success', 'Profil sponsor berhasil diperbarui.');
    }

    public function savePartnership(Request $request, Sponsor $sponsor, SponsorService $service, ?Partnership $partnership = null): RedirectResponse
    {
        abort_if($partnership && $partnership->sponsor_id !== $sponsor->id, 404);
        $data = $request->validate([
            'season_id' => ['nullable', 'exists:seasons,id'], 'tier' => ['required', Rule::in(['principal', 'official', 'supporting', 'community'])],
            'title' => ['nullable', 'string', 'max:100'], 'starts_at' => ['required', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'active', 'expired', 'archived'])], 'internal_notes' => ['nullable', 'string', 'max:2000'],
            'placement_keys' => ['sometimes', 'array'], 'placement_keys.*' => ['string', Rule::in(array_keys(SponsorService::PLACEMENTS))],
        ]);
        if (in_array($data['status'], ['scheduled', 'active'])) {
            abort_unless($request->user()->hasPermission('sponsors.publish'), 403);
        }
        $data['placement_keys'] = ['home.official_partners'];
        $overlap = $sponsor->partnerships()->whereKeyNot($partnership?->id)->whereNotIn('status', ['expired', 'archived'])
            ->whereDate('starts_at', '<=', $data['ends_at'] ?: '9999-12-31')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $data['starts_at']))->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['starts_at' => 'Periode kerja sama bertabrakan dengan periode yang sudah ada.']);
        }

        DB::transaction(function () use ($request, $sponsor, $partnership, $data) {
            $before = $partnership?->toArray();
            $values = [...$data, 'published_at' => $data['status'] === 'active' ? now() : null, 'updated_by' => $request->user()->id];
            unset($values['placement_keys']);
            if ($partnership) {
                $partnership->update($values);
            } else {
                $partnership = $sponsor->partnerships()->create([...$values, 'created_by' => $request->user()->id]);
            }
            $partnership->placements()->delete();
            foreach ($data['placement_keys'] ?? [] as $index => $key) {
                $partnership->placements()->create(['placement_key' => $key, 'display_order' => ($index + 1) * 10, 'is_visible' => true]);
            }
            $this->audit($request, $sponsor, $partnership, $before ? 'partnership_updated' : 'partnership_created', $before, $partnership->fresh()->toArray());
        });
        $service->forget();

        return back()->with('success', 'Periode kerja sama berhasil disimpan.');
    }

    public function archive(Request $request, Sponsor $sponsor, SponsorService $service): RedirectResponse
    {
        $before = $sponsor->toArray();
        $sponsor->update(['status' => 'archived', 'is_active' => false, 'updated_by' => $request->user()->id]);
        $sponsor->partnerships()->whereNotIn('status', ['expired', 'archived'])->update(['status' => 'archived']);
        $this->audit($request, $sponsor, null, 'archived', $before, $sponsor->fresh()->toArray());
        $service->forget();

        return back()->with('success', 'Sponsor berhasil diarsipkan.');
    }

    public function destroy(Request $request, Sponsor $sponsor, SponsorService $service): RedirectResponse
    {
        $this->audit($request, $sponsor, null, 'deleted', $sponsor->toArray(), null);
        $sponsor->delete();
        $service->forget();

        return back()->with('success', 'Sponsor dipindahkan ke arsip.');
    }

    private function validatedSponsor(Request $request, ?Sponsor $sponsor = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(['main', 'apparel', 'venue', 'community', 'media', 'supporting'])], 'description' => ['nullable', 'string', 'max:1500'],
            'logo_media_id' => ['nullable', 'exists:media,id'], 'light_logo_media_id' => ['nullable', 'exists:media,id'], 'dark_logo_media_id' => ['nullable', 'exists:media,id'],
            'website_url' => ['nullable', 'url', 'max:255'], 'instagram_url' => ['nullable', 'url', 'max:255'], 'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:255'], 'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'sponsor';
        $slug = $base;
        $suffix = 2;

        while (Sponsor::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function syncMedia(Sponsor $sponsor): void
    {
        MediaUsage::where('usable_type', Sponsor::class)->where('usable_id', $sponsor->id)->delete();
        foreach (['logo_media_id', 'light_logo_media_id', 'dark_logo_media_id'] as $field) {
            if ($sponsor->{$field}) {
                MediaUsage::create(['media_id' => $sponsor->{$field}, 'usable_type' => Sponsor::class, 'usable_id' => $sponsor->id, 'field_key' => $field]);
            }
        }
    }

    private function audit(Request $request, Sponsor $sponsor, ?Partnership $partnership, string $action, ?array $before, ?array $after): void
    {
        PartnerAudit::create(['sponsor_id' => $sponsor->id, 'partnership_id' => $partnership?->id, 'user_id' => $request->user()->id, 'action' => $action, 'before_payload' => $before, 'after_payload' => $after, 'created_at' => now()]);
    }
}
