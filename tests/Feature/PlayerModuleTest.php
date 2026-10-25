<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Player;
use App\Models\PlayerSeason;
use App\Models\Season;
use App\Models\User;
use App\Services\PlayerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlayerModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Cache::flush();
    }

    public function test_player_page_shows_setup_state_when_there_is_no_active_season(): void
    {
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        Season::query()->update(['is_active' => false]);

        $this->actingAs($superAdmin)
            ->get(route('admin.players.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Players')
                ->where('season', null)
                ->where('players.total', 0));

        $this->actingAs($superAdmin)
            ->post(route('admin.players.store'), [])
            ->assertSessionHasErrors('player');
    }

    public function test_seeded_players_are_available_to_admin_and_public_pages(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $this->actingAs($admin)->get(route('admin.players.index'))->assertOk()->assertInertia(fn (Assert $p) => $p->component('Admin/Players')->has('players.data', 8));
        $this->assertCount(8, app(PlayerService::class)->publicData()['players']);
        $this->get(route('team'))->assertInertia(fn (Assert $p) => $p->has('playerData.players', 8)->where('playerData.captain.name', 'Daniel'));
    }

    public function test_duplicate_shirt_number_is_rejected(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.players.store'), $this->payload(['display_name' => 'Baru', 'full_name' => 'Pemain Baru', 'slug' => 'pemain-baru', 'shirt_number' => 10]))->assertSessionHasErrors('shirt_number');
    }

    public function test_slug_is_generated_automatically_from_display_name(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $payload = $this->payload([
            'display_name' => 'Bintang Baru',
            'full_name' => 'Pemain Bintang Baru',
            'shirt_number' => 98,
        ]);
        unset($payload['slug']);

        $this->actingAs($admin)
            ->post(route('admin.players.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('players', [
            'display_name' => 'Bintang Baru',
            'slug' => 'bintang-baru',
        ]);
    }

    public function test_updating_captain_replaces_previous_captain(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $raka = Player::where('slug', 'raka')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.players.update', $raka), $this->payloadFrom($raka, ['is_captain' => true]))->assertSessionHasNoErrors();
        $season = Season::where('is_active', true)->firstOrFail();
        $this->assertSame(1, PlayerSeason::where('season_id', $season->id)->where('is_captain', true)->count());
        $this->assertTrue(PlayerSeason::where('player_id', $raka->id)->firstOrFail()->is_captain);
    }

    public function test_fourth_featured_player_is_rejected(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $fajar = Player::where('slug', 'fajar')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.players.update', $fajar), $this->payloadFrom($fajar, ['is_featured' => true, 'featured_order' => 1]))->assertSessionHasErrors('is_featured');
    }

    public function test_admin_can_publish_player_of_the_month(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $raka = Player::where('slug', 'raka')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.players.awards.save'), ['player_id' => $raka->id, 'period_month' => '2026-11', 'title' => 'Player of the Month', 'editorial' => 'Performa terbaik bulan November.', 'appearances' => 4, 'goals' => 2, 'assists' => 5, 'rating' => 9.1, 'is_published' => true])->assertSessionHasNoErrors();
        Cache::flush();
        $this->assertSame('Raka', app(PlayerService::class)->publicData()['playerOfMonth']['name']);
    }

    public function test_admin_can_save_player_of_the_month_as_draft(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $raka = Player::where('slug', 'raka')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.players.awards.save'), [
            'player_id' => $raka->id,
            'period_month' => '2026-12',
            'title' => 'Pemain Terbaik Desember',
            'editorial' => 'Masih disiapkan sebelum dipublikasikan.',
            'appearances' => 3,
            'goals' => 1,
            'assists' => 2,
            'rating' => 8.4,
            'is_published' => false,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('player_awards', [
            'player_id' => $raka->id,
            'period_month' => '2026-12',
            'status' => 'draft',
            'is_published' => false,
        ]);
    }

    public function test_regular_admin_cannot_delete_players(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.players.destroy', Player::firstOrFail()))->assertForbidden();
    }

    private function payload(array $overrides = []): array
    {
        return [...['full_name' => 'Testing Player', 'display_name' => 'Testing', 'slug' => 'testing', 'birth_date' => '2000-01-01', 'height_cm' => 175, 'dominant_foot' => 'Kanan', 'hometown' => 'Pekanbaru', 'biography' => 'Bio pemain.', 'primary_media_id' => null, 'image_focal_x' => 50, 'image_focal_y' => 40, 'status' => 'active', 'shirt_number' => 99, 'primary_position' => 'Forward', 'squad_status' => 'active', 'is_captain' => false, 'is_featured' => false, 'featured_order' => null, 'appearances' => 0, 'starts' => 0, 'minutes' => 0, 'goals' => 0, 'assists' => 0, 'clean_sheets' => 0, 'yellow_cards' => 0, 'red_cards' => 0, 'rating' => null, 'attendance_percent' => 80], ...$overrides];
    }

    private function payloadFrom(Player $p, array $overrides = []): array
    {
        $s = Season::where('is_active', true)->firstOrFail();
        $m = $p->memberships()->where('season_id', $s->id)->firstOrFail();
        $st = $p->stats()->where('season_id', $s->id)->firstOrFail();

        return $this->payload([...$p->only(['full_name', 'display_name', 'slug', 'height_cm', 'dominant_foot', 'hometown', 'biography', 'primary_media_id', 'image_focal_x', 'image_focal_y', 'status']), 'birth_date' => $p->birth_date?->format('Y-m-d'), ...$m->only(['shirt_number', 'primary_position', 'squad_status', 'is_captain', 'is_featured', 'featured_order']), ...$st->only(['appearances', 'starts', 'minutes', 'goals', 'assists', 'clean_sheets', 'yellow_cards', 'red_cards', 'rating', 'attendance_percent']), ...$overrides]);
    }
}
