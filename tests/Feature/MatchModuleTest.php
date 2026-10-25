<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeded_matches_are_available_to_admin_and_public(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->get(route('admin.matches.index'))->assertOk()->assertInertia(fn ($p) => $p->component('Admin/Matches')->has('matches.data', 6));
        $this->get(route('home'))->assertOk()->assertInertia(fn ($p) => $p->where('matchData.next.slug', 'cosmic-vs-rivals')->has('matchData.results', 3));
        $this->get(route('matches.show', 'cosmic-vs-rivals'))->assertOk()->assertInertia(fn ($p) => $p->component('Public/MatchDetail')->where('matchData.away.name', 'Rivals'));
    }

    public function test_admin_can_create_valid_competitive_match(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->post(route('admin.matches.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('matches', ['slug' => 'rivals-vs-cosmic-rematch']);
    }

    public function test_home_and_away_must_differ(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $cosmic = Club::where('slug', 'cosmic')->first();
        $this->actingAs($admin)->post(route('admin.matches.store'), $this->payload(['away_club_id' => $cosmic->id]))->assertSessionHasErrors('home_club_id');
    }

    public function test_competitive_clubs_must_be_season_participants(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $outsider = Club::create(['uuid' => fake()->uuid(), 'slug' => 'outsider', 'official_name' => 'Outsider', 'display_name' => 'Outsider', 'short_name' => 'OUT', 'type' => 'opponent', 'country' => 'Indonesia', 'status' => 'active']);
        $this->actingAs($admin)->post(route('admin.matches.store'), $this->payload(['away_club_id' => $outsider->id]))->assertSessionHasErrors('home_club_id');
    }

    public function test_completed_match_requires_score(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->post(route('admin.matches.store'), $this->payload(['status' => 'completed', 'home_score' => null, 'away_score' => null]))->assertSessionHasErrors('home_score');
    }

    public function test_featured_upcoming_match_is_unique(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->post(route('admin.matches.store'), $this->payload(['is_featured' => true]))->assertSessionHasNoErrors();
        $this->assertSame(1, GameMatch::where('is_featured', true)->count());
    }

    public function test_regular_admin_cannot_delete_matches(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->delete(route('admin.matches.destroy', GameMatch::first()))->assertForbidden();
    }

    public function test_admin_can_save_complete_match_lineup_data(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $match = GameMatch::where('slug', 'cosmic-vs-rivals')->firstOrFail();
        $player = Player::where('status', 'active')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.matches.lineups.sync', $match), [
            'lineups' => [
                ['club_id' => $match->home_club_id, 'player_id' => $player->id, 'guest_name' => '', 'role' => 'starter', 'position' => 'Forward', 'shirt_number' => 10, 'is_captain' => true, 'display_order' => 1],
                ['club_id' => $match->away_club_id, 'player_id' => null, 'guest_name' => 'Pemain Lawan', 'role' => 'substitute', 'position' => 'Defender', 'shirt_number' => 4, 'is_captain' => false, 'display_order' => 2],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('match_lineups', ['match_id' => $match->id, 'player_id' => $player->id, 'position' => 'Forward', 'shirt_number' => 10, 'is_captain' => true]);
        $this->assertDatabaseHas('match_lineups', ['match_id' => $match->id, 'guest_name' => 'Pemain Lawan', 'role' => 'substitute']);
    }

    public function test_lineup_rejects_duplicate_players_and_shirt_numbers_per_club(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $match = GameMatch::where('slug', 'cosmic-vs-rivals')->firstOrFail();
        $player = Player::where('status', 'active')->firstOrFail();
        $row = ['club_id' => $match->home_club_id, 'player_id' => $player->id, 'guest_name' => '', 'role' => 'starter', 'position' => 'Forward', 'shirt_number' => 10, 'is_captain' => false, 'display_order' => 1];

        $this->actingAs($admin)->put(route('admin.matches.lineups.sync', $match), [
            'lineups' => [$row, [...$row, 'display_order' => 2]],
        ])->assertSessionHasErrors('lineups');
    }

    private function payload(array $changes = []): array
    {
        $season = Season::where('is_featured', true)->first();
        $cosmic = Club::where('slug', 'cosmic')->first();
        $rivals = Club::where('slug', 'rivals')->first();

        return array_merge(['slug' => 'rivals-vs-cosmic-rematch', 'season_id' => $season->id, 'match_type' => 'competitive', 'matchday' => 8, 'round_name' => '', 'home_club_id' => $cosmic->id, 'away_club_id' => $rivals->id, 'kickoff_at' => '2026-11-14 19:30:00', 'timezone' => 'Asia/Jakarta', 'venue_name' => 'Aero Sport Center', 'venue_address' => 'Pekanbaru', 'status' => 'scheduled', 'home_score' => null, 'away_score' => null, 'count_for_standings' => true, 'is_featured' => false, 'is_public' => true, 'cover_media_id' => null, 'short_report' => '', 'attendance' => null], $changes);
    }
}
