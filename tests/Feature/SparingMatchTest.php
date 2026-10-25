<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\GameMatch;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SparingMatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sparing_can_be_created_without_season_and_never_counts_for_standings(): void
    {
        $admin = User::where('email', 'superadmin@cosmic.test')->firstOrFail();
        $home = $this->club('cosmic-sparing', 'COSMIC Sparing', true);
        $away = $this->club('lawan-sparing', 'Lawan Sparing');

        $this->actingAs($admin)->post(route('admin.matches.store'), [
            'slug' => 'cosmic-sparing-vs-lawan-sparing',
            'season_id' => null,
            'match_type' => 'sparing',
            'matchday' => 9,
            'round_name' => 'Babak latihan',
            'home_club_id' => $home->id,
            'away_club_id' => $away->id,
            'kickoff_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => 'Lapangan Uji',
            'venue_address' => 'Pekanbaru',
            'status' => 'scheduled',
            'home_score' => null,
            'away_score' => null,
            'count_for_standings' => true,
            'is_featured' => true,
            'is_public' => true,
            'cover_media_id' => null,
            'short_report' => null,
            'attendance' => null,
        ])->assertSessionHasNoErrors();

        $match = GameMatch::where('slug', 'cosmic-sparing-vs-lawan-sparing')->firstOrFail();
        $this->assertNull($match->season_id);
        $this->assertNull($match->matchday);
        $this->assertNull($match->round_name);
        $this->assertFalse($match->count_for_standings);
        $this->assertTrue($match->is_featured);

        $this->get(route('home'))->assertOk()->assertInertia(fn ($page) => $page
            ->where('matchData.next.slug', $match->slug)
            ->where('matchData.next.type', 'sparing')
            ->where('matchData.next.competition', 'Sparing'));
    }

    public function test_admin_can_filter_sparing_matches(): void
    {
        $admin = User::where('email', 'superadmin@cosmic.test')->firstOrFail();
        $home = $this->club('cosmic-filter', 'COSMIC Filter', true);
        $away = $this->club('lawan-filter', 'Lawan Filter');
        GameMatch::create([
            'uuid' => Str::uuid(), 'slug' => 'sparing-filter', 'match_type' => 'sparing',
            'home_club_id' => $home->id, 'away_club_id' => $away->id,
            'status' => 'draft', 'count_for_standings' => false,
        ]);

        $this->actingAs($admin)->get(route('admin.matches.index', ['match_type' => 'sparing']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('matches.data', 1)
                ->where('matches.data.0.match_type', 'sparing')
                ->where('filters.match_type', 'sparing'));
    }

    private function club(string $slug, string $name, bool $cosmic = false): Club
    {
        return Club::create([
            'uuid' => Str::uuid(), 'slug' => $slug, 'official_name' => $name,
            'display_name' => $name, 'short_name' => strtoupper(substr($slug, 0, 3)),
            'type' => $cosmic ? 'internal' : 'opponent', 'is_cosmic' => $cosmic,
            'status' => 'active',
        ]);
    }
}
