<?php

namespace Tests\Feature;

use App\Models\AwardType;
use App\Models\Club;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\NewsArticle;
use App\Models\PageSection;
use App\Models\Player;
use App\Models\Season;
use App\Models\Sponsor;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductionBaselineSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_seed_contains_editorial_baseline_without_operational_demo_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertSame(0, Player::count());
        $this->assertSame(0, Staff::count());
        $this->assertSame(0, Club::count());
        $this->assertSame(0, Competition::count());
        $this->assertSame(0, Season::count());
        $this->assertSame(0, GameMatch::count());
        $this->assertSame(0, Sponsor::count());
        $this->assertSame(0, AwardType::count());
        $this->assertSame(0, NewsArticle::count());

        foreach (array_keys(config('section-defaults')) as $sectionKey) {
            $section = PageSection::where('section_key', $sectionKey)->firstOrFail();

            $this->assertNotEmpty($section->publishedContent?->content, "Published content {$sectionKey} kosong.");
            $this->assertNotEmpty($section->draftContent?->content, "Draft content {$sectionKey} kosong.");
        }
    }

    public function test_standings_admin_page_can_open_without_a_competition_season(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superadmin = User::where('email', 'superadmin@cosmic.test')->firstOrFail();

        $this->actingAs($superadmin)
            ->get(route('admin.standings.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Standings')
                ->where('seasonId', null)
                ->has('seasons', 0)
                ->has('standings', 0)
                ->has('clubs', 0));
    }

    public function test_player_awards_admin_page_can_open_without_a_competition_season(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superadmin = User::where('email', 'superadmin@cosmic.test')->firstOrFail();

        $this->actingAs($superadmin)
            ->get(route('admin.player-awards.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlayerAwards')
                ->where('seasonId', null)
                ->has('seasons', 0)
                ->has('awards', 0)
                ->has('candidates', 0)
                ->has('matches', 0)
                ->where('can.types', true));
    }
}
