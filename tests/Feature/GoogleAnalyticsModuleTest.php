<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AnalyticsSetting;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GoogleAnalyticsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_superadmin_can_save_ga4_configuration_securely(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $credentials = ['type' => 'service_account', 'client_email' => 'analytics@example.test', 'private_key' => "-----BEGIN PRIVATE KEY-----\ntest\n-----END PRIVATE KEY-----\n"];

        $this->actingAs($admin)->post(route('admin.analytics.settings.update'), [
            '_method' => 'PUT', 'enabled' => '1', 'measurement_id' => 'G-ABC123XYZ',
            'property_id' => '123456789', 'cache_minutes' => '30',
            'service_account_file' => UploadedFile::fake()->createWithContent('service-account.json', json_encode($credentials)),
        ])->assertSessionHasNoErrors();

        $setting = AnalyticsSetting::firstOrFail();
        $this->assertTrue($setting->enabled);
        $this->assertSame('analytics@example.test', $setting->service_account['client_email']);
        $this->assertStringNotContainsString('BEGIN PRIVATE KEY', $setting->getRawOriginal('service_account'));

        $this->actingAs($admin)->get(route('admin.club-profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('analyticsSetting.has_credentials', true)
                ->missing('analyticsSetting.service_account'));
    }

    public function test_invalid_measurement_id_and_invalid_credentials_are_rejected(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.analytics.settings.update'), [
            '_method' => 'PUT', 'enabled' => '1', 'measurement_id' => 'UA-INVALID',
            'property_id' => 'abc', 'cache_minutes' => '5',
            'service_account_file' => UploadedFile::fake()->createWithContent('invalid.json', '{}'),
        ])->assertSessionHasErrors(['measurement_id', 'property_id', 'cache_minutes']);
    }

    public function test_analytics_page_handles_unconfigured_state_without_error(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics')
                ->where('analytics.available', false)
                ->where('analytics.status.configured', false));
    }

    public function test_regular_admin_cannot_change_ga4_credentials(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'role_id' => Role::where('slug', 'admin')->firstOrFail()->id, 'email_verified_at' => now()]);

        $this->actingAs($admin)->put(route('admin.analytics.settings.update'), [
            'enabled' => false, 'cache_minutes' => 30,
        ])->assertForbidden();
    }

    public function test_public_page_does_not_render_tracking_when_ga4_is_disabled(): void
    {
        AnalyticsSetting::current()->update(['enabled' => false, 'measurement_id' => 'G-ABC123XYZ']);
        $this->get(route('home'))->assertOk()->assertDontSee('googletagmanager.com/gtag/js', false);
    }

    public function test_public_page_renders_tracking_when_ga4_is_enabled(): void
    {
        AnalyticsSetting::current()->update([
            'enabled' => true, 'measurement_id' => 'G-ABC123XYZ', 'property_id' => '123456789',
            'service_account' => ['type' => 'service_account', 'client_email' => 'analytics@example.test', 'private_key' => 'secret'],
        ]);

        $this->get(route('home'))->assertOk()->assertSee('googletagmanager.com/gtag/js?id=G-ABC123XYZ', false);
    }

    public function test_refresh_always_redirects_to_report_page_instead_of_post_endpoint(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $setting = AnalyticsSetting::current();
        $setting->update(['enabled' => false]);
        Cache::flush();

        $this->actingAs($admin)->post(route('admin.analytics.refresh'), [
            'start_date' => '2026-10-11',
            'end_date' => '2026-11-09',
        ])->assertRedirect(route('admin.analytics.index', [
            'period' => 'custom',
            'start_date' => '2026-10-11',
            'end_date' => '2026-11-09',
        ]));
    }
}
