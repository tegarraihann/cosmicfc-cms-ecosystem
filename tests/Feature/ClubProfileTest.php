<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ClubProfile;
use App\Models\User;
use App\Services\ClubProfileService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClubProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Cache::flush();
    }

    public function test_admin_can_open_club_profile_editor(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.club-profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ClubProfile')
                ->where('profile.club_name', 'COSMIC')
                ->where('canPublish', false));
    }

    public function test_saving_draft_does_not_change_public_profile(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $payload = $this->draftPayload(['club_name' => 'COSMIC DRAFT']);

        $this->actingAs($admin)->put(route('admin.club-profile.update'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame('COSMIC DRAFT', app(ClubProfileService::class)->draft()['identity']['name']);
        $this->assertSame('COSMIC', app(ClubProfileService::class)->published()['identity']['name']);
    }

    public function test_superadmin_can_publish_draft_and_clear_public_cache(): void
    {
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $draft = ClubProfile::where('status', 'draft')->firstOrFail();
        $draft->update(['club_name' => 'COSMIC PEKANBARU', 'accent_color' => '#2299ff']);

        app(ClubProfileService::class)->published();
        $this->actingAs($superAdmin)->post(route('admin.club-profile.publish'))
            ->assertSessionHasNoErrors();

        $published = app(ClubProfileService::class)->published();
        $this->assertSame('COSMIC PEKANBARU', $published['identity']['name']);
        $this->assertSame('#2299ff', $published['branding']['accentColor']);
        $this->assertDatabaseHas('club_profile_audits', ['action' => 'published']);
    }

    public function test_regular_admin_cannot_publish_profile(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.club-profile.publish'))->assertForbidden();
    }

    public function test_public_page_receives_published_site_profile(): void
    {
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('site.identity.name', 'COSMIC')
            ->where('site.location.city', 'Pekanbaru')
            ->where('site.branding.accentColor', '#45b7ff'));
    }

    public function test_public_profile_exposes_a_stable_founded_date_and_uses_its_year(): void
    {
        $profile = ClubProfile::where('status', 'published')->firstOrFail();
        $profile->update([
            'founded_at' => '2025-01-11',
            'founded_year' => 2026,
        ]);

        app(ClubProfileService::class)->forget();

        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('site.founded_at', '2025-01-11')
            ->where('site.identity.foundedYear', 2025));
    }

    public function test_social_profiles_support_extended_and_custom_platforms(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $socials = [
            ['platform' => 'linkedin', 'custom_label' => '', 'url' => 'https://linkedin.com/company/cosmic', 'handle' => '@cosmic', 'active' => true],
            ['platform' => 'other', 'custom_label' => 'Komunitas Lokal', 'url' => 'https://example.com/cosmic', 'handle' => '', 'active' => false],
        ];

        $this->actingAs($admin)->put(route('admin.club-profile.update'), $this->draftPayload(['socials' => $socials]))
            ->assertSessionHasNoErrors();

        $saved = ClubProfile::where('status', 'draft')->firstOrFail()->socials;
        $this->assertSame('linkedin', $saved[0]['platform']);
        $this->assertSame('Komunitas Lokal', $saved[1]['custom_label']);
        $this->assertFalse($saved[1]['active']);
    }

    public function test_profile_checkboxes_are_saved_as_boolean_values(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $profile = ClubProfile::where('status', 'draft')->firstOrFail();
        $socials = $profile->socials;
        $socials[0]['active'] = false;
        $website = $profile->website;
        $website['maintenance'] = true;

        $this->actingAs($admin)->put(route('admin.club-profile.update'), $this->draftPayload([
            'socials' => $socials,
            'website' => $website,
        ]))->assertSessionHasNoErrors();

        $saved = ClubProfile::where('status', 'draft')->firstOrFail();
        $this->assertFalse($saved->socials[0]['active']);
        $this->assertTrue($saved->website['maintenance']);
    }

    private function draftPayload(array $overrides = []): array
    {
        $profile = ClubProfile::where('status', 'draft')->firstOrFail();
        $keys = ['club_name', 'short_name', 'tagline', 'short_description', 'founded_at', 'founded_year', 'city', 'country', 'home_base', 'address', 'map_url', 'email', 'phone', 'whatsapp', 'contact_person', 'logo_url', 'alternate_logo_url', 'favicon_url', 'share_image_url', 'primary_color', 'accent_color', 'socials', 'seo', 'website'];
        $payload = Arr::only($profile->toArray(), $keys);
        $payload['founded_at'] = $profile->founded_at?->format('Y-m-d');

        return [...$payload, ...$overrides];
    }
}
