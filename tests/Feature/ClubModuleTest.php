<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeded_clubs_are_available_to_admin_and_public_pages(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.clubs.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Clubs')->has('clubs.data', 7));
        $this->get(route('home'))->assertOk()->assertInertia(fn ($page) => $page->has('clubData.clubs', 7)->where('clubData.cosmic.shortName', 'CSM'));
    }

    public function test_admin_can_create_opponent_with_aliases(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $payload = $this->payload(['official_name' => 'Pekanbaru United', 'display_name' => 'PKU United', 'short_name' => 'PKU', 'aliases' => ['Pekanbaru Utd']]);
        unset($payload['slug']);
        $this->actingAs($admin)->post(route('admin.clubs.store'), $payload)->assertSessionHasNoErrors();
        $club = Club::where('slug', 'pku-united')->firstOrFail();
        $this->assertDatabaseHas('club_aliases', ['club_id' => $club->id, 'alias' => 'Pekanbaru Utd']);
    }

    public function test_only_one_internal_cosmic_club_is_allowed(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.clubs.store'), $this->payload(['official_name' => 'Cosmic Academy', 'display_name' => 'Cosmic Academy', 'short_name' => 'CSA', 'slug' => 'cosmic-academy', 'type' => 'internal', 'is_cosmic' => true]))->assertSessionHasErrors('is_cosmic');
    }

    public function test_normalized_alias_cannot_belong_to_another_club(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.clubs.store'), $this->payload(['official_name' => 'Rivals FC', 'display_name' => 'Rivals FC', 'short_name' => 'RFC', 'slug' => 'rivals-fc', 'aliases' => ['Ri vals']]))->assertSessionHasErrors('aliases');
    }

    public function test_cosmic_cannot_be_archived_and_regular_admin_cannot_delete(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $cosmic = Club::where('is_cosmic', true)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.clubs.archive', $cosmic))->assertSessionHasErrors('club');
        $this->actingAs($admin)->delete(route('admin.clubs.destroy', Club::where('slug', 'rivals')->first()))->assertForbidden();
    }

    public function test_superadmin_can_delete_an_unused_club(): void
    {
        $superadmin = User::where('email', 'superadmin@cosmic.test')->firstOrFail();
        $club = Club::create([
            'uuid' => fake()->uuid(),
            'slug' => 'klub-sementara',
            'official_name' => 'Klub Sementara',
            'display_name' => 'Klub Sementara',
            'short_name' => 'KSM',
            'type' => 'guest',
            'country' => 'Indonesia',
            'primary_color' => '#45B7FF',
            'secondary_color' => '#FFFFFF',
            'is_cosmic' => false,
            'status' => 'active',
        ]);

        $this->actingAs($superadmin)->delete(route('admin.clubs.destroy', $club))->assertSessionHasNoErrors();
        $this->assertSoftDeleted('clubs', ['id' => $club->id]);
    }

    private function payload(array $changes = []): array
    {
        return array_merge(['official_name' => 'New Opponent', 'display_name' => 'New Opponent', 'short_name' => 'NEW', 'slug' => 'new-opponent', 'type' => 'opponent', 'city' => 'Pekanbaru', 'country' => 'Indonesia', 'founded_year' => 2026, 'primary_media_id' => null, 'alternate_media_id' => null, 'primary_color' => '#45B7FF', 'secondary_color' => '#FFFFFF', 'website_url' => null, 'instagram_url' => null, 'contact_email' => null, 'default_venue' => null, 'is_cosmic' => false, 'status' => 'active', 'aliases' => []], $changes);
    }
}
