<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\GameMatch;
use Database\Seeders\November2026ScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class November2026ScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_november_schedule_without_duplicates(): void
    {
        $this->seed(November2026ScheduleSeeder::class);
        $this->seed(November2026ScheduleSeeder::class);

        $this->assertSame(5, Club::count());
        $this->assertSame(4, GameMatch::count());

        $this->assertDatabaseHas('matches', [
            'slug' => 'cosmic-vs-medima-fc-2026-11-03',
            'kickoff_at' => '2026-11-03 20:00:00',
            'venue_name' => 'Astaka Arena',
            'status' => 'scheduled',
            'match_type' => 'sparing',
            'is_public' => true,
        ]);

        $this->assertDatabaseHas('matches', [
            'slug' => 'cosmic-vs-lo-fc-2026-11-05',
            'kickoff_at' => '2026-11-05 21:00:00',
            'venue_name' => 'Take Off',
        ]);

        $this->assertDatabaseHas('matches', [
            'slug' => 'cosmic-vs-gaspol-fc-2026-11-07',
            'kickoff_at' => '2026-11-07 21:00:00',
            'venue_name' => 'Astaka Arena',
        ]);

        $this->assertDatabaseHas('matches', [
            'slug' => 'cosmic-vs-al-khaf-fc-2026-11-10',
            'kickoff_at' => null,
            'venue_name' => null,
            'status' => 'draft',
            'is_public' => false,
        ]);
    }
}
