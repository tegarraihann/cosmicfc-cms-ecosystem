<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSetting;
use App\Models\ClubProfile;
use App\Models\ClubProfileAudit;
use App\Services\ClubProfileService;
use App\Services\MediaLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClubProfileController extends Controller
{
    public function edit(): Response
    {
        $draft = ClubProfile::where('status', 'draft')->firstOrFail();
        $published = ClubProfile::where('status', 'published')->firstOrFail();

        return Inertia::render('Admin/ClubProfile', [
            'profile' => $this->formData($draft),
            'published' => ['version' => $published->version, 'published_at' => $published->published_at?->translatedFormat('d M Y, H:i')],
            'hasChanges' => $this->payload($draft) !== $this->payload($published),
            'canPublish' => request()->user()->hasPermission('club-profile.publish'),
            'analyticsSetting' => request()->user()->hasPermission('analytics.configure') ? $this->analyticsFormData() : null,
        ]);
    }

    public function update(Request $request, MediaLibraryService $mediaLibrary): RedirectResponse
    {
        $data = $this->validated($request);
        $draft = ClubProfile::where('status', 'draft')->firstOrFail();
        $before = $this->payload($draft);
        $draft->update([...$data, 'version' => $draft->version + 1, 'created_by' => $request->user()->id]);
        $mediaLibrary->syncUsages($draft, Arr::only($data, ['logo_url', 'alternate_logo_url', 'favicon_url', 'share_image_url']));
        ClubProfileAudit::create(['user_id' => $request->user()->id, 'action' => 'draft_saved', 'before_payload' => $before, 'after_payload' => $this->payload($draft->fresh())]);

        return back()->with('success', 'Draft profil klub berhasil disimpan.');
    }

    public function publish(Request $request, ClubProfileService $service): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $draft = ClubProfile::where('status', 'draft')->firstOrFail();
            $published = ClubProfile::where('status', 'published')->firstOrFail();
            $before = $this->payload($published);
            $published->update([...$this->payload($draft), 'version' => $published->version + 1, 'published_by' => $request->user()->id, 'published_at' => now()]);
            ClubProfileAudit::create(['user_id' => $request->user()->id, 'action' => 'published', 'before_payload' => $before, 'after_payload' => $this->payload($published->fresh())]);
        });
        $service->forget();

        return back()->with('success', 'Profil klub berhasil dipublikasikan.');
    }

    public function discard(Request $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $draft = ClubProfile::where('status', 'draft')->firstOrFail();
            $published = ClubProfile::where('status', 'published')->firstOrFail();
            $draft->update([...$this->payload($published), 'version' => $draft->version + 1, 'created_by' => $request->user()->id]);
            ClubProfileAudit::create(['user_id' => $request->user()->id, 'action' => 'draft_discarded']);
        });

        return back()->with('success', 'Draft dikembalikan ke profil yang dipublikasikan.');
    }

    public function preview(): RedirectResponse
    {
        return redirect()->away(URL::temporarySignedRoute('preview.profile', now()->addMinutes(30)));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'club_name' => ['required', 'string', 'max:120'], 'short_name' => ['required', 'string', 'max:30'], 'tagline' => ['nullable', 'string', 'max:180'], 'short_description' => ['nullable', 'string', 'max:500'],
            'founded_at' => ['nullable', 'date', 'before_or_equal:today'], 'founded_year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'city' => ['nullable', 'string', 'max:100'], 'country' => ['nullable', 'string', 'max:100'], 'home_base' => ['nullable', 'string', 'max:160'], 'address' => ['nullable', 'string', 'max:600'], 'map_url' => ['nullable', 'url', 'max:500'],
            'email' => ['nullable', 'email', 'max:160'], 'phone' => ['nullable', 'string', 'max:30'], 'whatsapp' => ['nullable', 'regex:/^[0-9+\-\s()]{8,25}$/'], 'contact_person' => ['nullable', 'string', 'max:120'],
            'logo_url' => ['nullable', 'string', 'max:500'], 'alternate_logo_url' => ['nullable', 'string', 'max:500'], 'favicon_url' => ['nullable', 'string', 'max:500'], 'share_image_url' => ['nullable', 'string', 'max:500'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'socials' => ['array', 'max:8'],
            'socials.*.platform' => ['required', Rule::in(['instagram', 'tiktok', 'youtube', 'facebook', 'x', 'threads', 'linkedin', 'telegram', 'discord', 'website', 'other'])],
            'socials.*.custom_label' => ['nullable', 'required_if:socials.*.platform,other', 'string', 'max:50'],
            'socials.*.url' => ['nullable', 'string', 'max:500'],
            'socials.*.handle' => ['nullable', 'string', 'max:100'],
            'socials.*.active' => ['boolean'],
            'seo' => ['array'], 'seo.default_title' => ['nullable', 'string', 'max:70'], 'seo.default_description' => ['nullable', 'string', 'max:170'], 'seo.keywords' => ['nullable', 'string', 'max:500'], 'seo.indexing_enabled' => ['boolean'],
            'seo.pages' => ['array'],
            'seo.pages.home.title' => ['nullable', 'string', 'max:70'], 'seo.pages.home.description' => ['nullable', 'string', 'max:170'],
            'seo.pages.schedule.title' => ['nullable', 'string', 'max:70'], 'seo.pages.schedule.description' => ['nullable', 'string', 'max:170'],
            'seo.pages.league.title' => ['nullable', 'string', 'max:70'], 'seo.pages.league.description' => ['nullable', 'string', 'max:170'],
            'seo.pages.team.title' => ['nullable', 'string', 'max:70'], 'seo.pages.team.description' => ['nullable', 'string', 'max:170'],
            'seo.pages.news.title' => ['nullable', 'string', 'max:70'], 'seo.pages.news.description' => ['nullable', 'string', 'max:170'],
            'website' => ['array'], 'website.timezone' => ['required', Rule::in(['Asia/Jakarta'])], 'website.locale' => ['required', Rule::in(['id', 'en'])], 'website.maintenance' => ['boolean'], 'website.analytics_id' => ['nullable', 'regex:/^(G-[A-Z0-9]+|)$/'],
        ]);
    }

    private function payload(ClubProfile $profile): array
    {
        return Arr::only($profile->toArray(), ['club_name', 'short_name', 'tagline', 'short_description', 'founded_at', 'founded_year', 'city', 'country', 'home_base', 'address', 'map_url', 'email', 'phone', 'whatsapp', 'contact_person', 'logo_url', 'alternate_logo_url', 'favicon_url', 'share_image_url', 'primary_color', 'accent_color', 'socials', 'seo', 'website']);
    }

    private function formData(ClubProfile $profile): array
    {
        $data = $this->payload($profile);
        $data['founded_at'] = $profile->founded_at?->format('Y-m-d');
        $data['seo'] = array_replace_recursive([
            'default_title' => '', 'default_description' => '', 'keywords' => '', 'indexing_enabled' => true,
            'pages' => array_fill_keys(['home', 'schedule', 'league', 'team', 'news'], ['title' => '', 'description' => '']),
        ], is_array($data['seo'] ?? null) ? $data['seo'] : []);

        return $data;
    }

    private function analyticsFormData(): array
    {
        $setting = AnalyticsSetting::current();

        return [
            'enabled' => $setting->enabled,
            'measurement_id' => $setting->measurement_id,
            'property_id' => $setting->property_id,
            'cache_minutes' => $setting->cache_minutes,
            'has_credentials' => filled($setting->service_account),
            'connection_status' => $setting->connection_status,
            'last_error' => $setting->last_error,
            'last_tested_at' => $setting->last_tested_at?->locale('id')->translatedFormat('d M Y, H.i').' WIB',
        ];
    }
}
