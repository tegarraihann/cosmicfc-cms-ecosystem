<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\GameMatch;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScheduleHeroDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_exposes_sparing_context_when_no_season_exists(): void
    {
        $this->seed(DatabaseSeeder::class);
        $home = $this->club('cosmic', 'COSMIC', true);
        $away = $this->club('lawan-sparing', 'Lawan Sparing');

        GameMatch::create([
            'uuid' => (string) Str::uuid(),
            'slug' => 'cosmic-vs-lawan-sparing',
            'match_type' => 'sparing',
            'home_club_id' => $home->id,
            'away_club_id' => $away->id,
            'kickoff_at' => now()->addDay(),
            'duration_minutes' => 120,
            'timezone' => 'Asia/Jakarta',
            'status' => 'scheduled',
            'score_mode' => 'automatic',
            'count_for_standings' => false,
            'is_featured' => true,
            'is_public' => true,
            'published_at' => now(),
        ]);

        $this->get(route('schedule'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('competitionData.competition', null)
                ->where('competitionData.season', null)
                ->where('matchData.upcoming.0.type', 'sparing'));
    }

    private function club(string $slug, string $name, bool $cosmic = false): Club
    {
        return Club::create([
            'uuid' => (string) Str::uuid(),
            'slug' => $slug,
            'official_name' => $name,
            'display_name' => $name,
            'short_name' => Str::upper(Str::substr($slug, 0, 6)),
            'type' => $cosmic ? 'internal' : 'opponent',
            'is_cosmic' => $cosmic,
            'status' => 'active',
        ]);
    }
}
