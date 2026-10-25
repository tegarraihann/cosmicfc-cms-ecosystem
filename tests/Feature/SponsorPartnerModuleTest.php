<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Partnership;
use App\Models\Season;
use App\Models\Sponsor;
use App\Models\User;
use App\Services\SponsorService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorPartnerModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_sponsor_with_media_usage(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $media = Media::create(['uuid' => fake()->uuid(), 'disk' => 'public', 'directory' => 'sponsors', 'filename' => 'partner.png', 'original_name' => 'partner.png', 'mime_type' => 'image/png', 'extension' => 'png', 'size_bytes' => 100, 'checksum' => fake()->sha256(), 'title' => 'Partner Logo', 'folder' => 'sponsors', 'status' => 'active', 'uploaded_by' => $admin->id]);

        $this->actingAs($admin)->post(route('admin.sponsors.store'), $this->profile(['logo_media_id' => $media->id]))->assertSessionHasNoErrors();
        $sponsor = Sponsor::where('slug', 'blue-partner')->firstOrFail();
        $this->assertDatabaseHas('media_usages', ['media_id' => $media->id, 'usable_id' => $sponsor->id, 'field_key' => 'logo_media_id']);
        $this->assertDatabaseHas('partner_audits', ['sponsor_id' => $sponsor->id, 'action' => 'created']);
    }

    public function test_slug_and_display_order_are_generated_automatically(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        Sponsor::create($this->profile([
            'name' => 'Blue Partner',
            'slug' => 'blue-partner',
            'sort_order' => 20,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]));

        $payload = $this->profile(['name' => 'Blue Partner']);
        unset($payload['slug'], $payload['sort_order']);

        $this->actingAs($admin)
            ->post(route('admin.sponsors.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('sponsors', [
            'name' => 'Blue Partner',
            'slug' => 'blue-partner-2',
            'sort_order' => 30,
        ]);
    }

    public function test_optional_contact_fields_can_be_left_empty_and_slug_stays_stable_on_update(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $payload = $this->profile([
            'website_url' => null,
            'instagram_url' => null,
            'contact_name' => null,
            'contact_email' => null,
        ]);
        unset($payload['slug'], $payload['sort_order']);

        $this->actingAs($admin)
            ->post(route('admin.sponsors.store'), $payload)
            ->assertSessionHasNoErrors();

        $sponsor = Sponsor::where('name', 'Blue Partner')->firstOrFail();
        $originalSlug = $sponsor->slug;
        $payload['name'] = 'Blue Partner Indonesia';

        $this->actingAs($admin)
            ->put(route('admin.sponsors.update', $sponsor), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame($originalSlug, $sponsor->fresh()->slug);
        $this->assertNull($sponsor->fresh()->contact_email);
    }

    public function test_sponsor_picker_includes_active_images_from_general_folder(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        Media::create(['uuid' => fake()->uuid(), 'disk' => 'public', 'directory' => 'uploads/general', 'filename' => 'general-logo.png', 'original_name' => 'general-logo.png', 'mime_type' => 'image/png', 'extension' => 'png', 'size_bytes' => 100, 'checksum' => fake()->sha256(), 'title' => 'General Logo', 'folder' => 'general', 'status' => 'active', 'uploaded_by' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.sponsors.index'))->assertOk()->assertInertia(fn ($page) => $page
            ->has('media', 1)
            ->where('media.0.title', 'General Logo')
            ->where('media.0.folder', 'general'));
    }

    public function test_sponsor_summary_stats_are_not_changed_by_list_filters(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        Sponsor::create($this->profile(['created_by' => $admin->id, 'updated_by' => $admin->id]));

        $this->actingAs($admin)
            ->get(route('admin.sponsors.index', ['search' => 'tidak-ditemukan']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('sponsors', 0)
                ->where('stats.total', 1)
                ->where('stats.active', 1)
                ->where('stats.placement_locations', 1));
    }

    public function test_active_partnership_is_published_to_requested_placement_without_private_contact(): void
    {
        $superadmin = User::where('email', 'superadmin@cosmic.test')->first();
        $this->actingAs($superadmin)->post(route('admin.sponsors.store'), $this->profile(['status' => 'active', 'contact_email' => 'private@partner.test']))->assertSessionHasNoErrors();
        $sponsor = Sponsor::where('slug', 'blue-partner')->firstOrFail();
        $this->actingAs($superadmin)->post(route('admin.sponsors.partnerships.save', ['sponsor' => $sponsor]), $this->term())->assertRedirect()->assertSessionHasNoErrors();

        $public = app(SponsorService::class)->publicData();
        $this->assertSame('Blue Partner', $public['placements']['home.official_partners'][0]['name']);
        $this->assertArrayNotHasKey('site.footer', $public['placements']);
        $this->assertArrayNotHasKey('contact_email', $public['placements']['home.official_partners'][0]);
        $this->get(route('home'))->assertOk()->assertInertia(fn ($page) => $page->has('sponsorData'));
    }

    public function test_overlapping_partnership_is_rejected(): void
    {
        $superadmin = User::where('email', 'superadmin@cosmic.test')->first();
        $sponsor = Sponsor::create($this->profile(['created_by' => $superadmin->id, 'updated_by' => $superadmin->id]));
        $this->actingAs($superadmin)->post(route('admin.sponsors.partnerships.save', $sponsor), $this->term())->assertRedirect()->assertSessionHasNoErrors();
        $this->actingAs($superadmin)->post(route('admin.sponsors.partnerships.save', $sponsor), $this->term(['starts_at' => today()->addDays(10)->toDateString()]))->assertSessionHasErrors('starts_at');
        $this->assertSame(1, Partnership::where('sponsor_id', $sponsor->id)->count());
    }

    public function test_regular_admin_cannot_publish_partnership(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $sponsor = Sponsor::create($this->profile(['created_by' => $admin->id, 'updated_by' => $admin->id]));
        $this->actingAs($admin)->post(route('admin.sponsors.partnerships.save', $sponsor), $this->term())->assertForbidden();
    }

    public function test_partnership_uses_home_official_partners_as_default_placement(): void
    {
        $superadmin = User::where('email', 'superadmin@cosmic.test')->first();
        $sponsor = Sponsor::create($this->profile(['created_by' => $superadmin->id, 'updated_by' => $superadmin->id]));
        $this->actingAs($superadmin)->post(route('admin.sponsors.partnerships.save', $sponsor), $this->term(['placement_keys' => []]))->assertSessionHasNoErrors();

        $partnership = $sponsor->partnerships()->firstOrFail();
        $this->assertDatabaseHas('partner_placements', [
            'partnership_id' => $partnership->id,
            'placement_key' => 'home.official_partners',
            'is_visible' => true,
        ]);
        $this->assertSame(1, $partnership->placements()->count());
    }

    private function profile(array $changes = []): array
    {
        return array_merge(['name' => 'Blue Partner', 'slug' => 'blue-partner', 'category' => 'main', 'description' => 'Partner utama COSMIC.', 'logo_media_id' => null, 'light_logo_media_id' => null, 'dark_logo_media_id' => null, 'website_url' => 'https://partner.test', 'instagram_url' => null, 'contact_name' => 'Internal PIC', 'contact_email' => null, 'sort_order' => 10, 'status' => 'active', 'is_active' => true], $changes);
    }

    private function term(array $changes = []): array
    {
        return array_merge(['season_id' => Season::where('is_featured', true)->value('id'), 'tier' => 'principal', 'title' => 'Main Partner', 'starts_at' => today()->subDay()->toDateString(), 'ends_at' => today()->addMonth()->toDateString(), 'status' => 'active', 'internal_notes' => 'Kontrak internal.', 'placement_keys' => ['home.official_partners', 'site.footer']], $changes);
    }
}
