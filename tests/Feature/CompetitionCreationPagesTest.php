<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionCreationPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_superadmin_can_open_dedicated_competition_and_season_pages(): void
    {
        $admin = User::firstOrFail();
        $competition = Competition::create($this->competitionData($admin->id));

        $this->actingAs($admin)
            ->get(route('admin.competitions.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/CompetitionCreate'));

        $this->actingAs($admin)
            ->get(route('admin.competitions.seasons.create', ['competition' => $competition->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/SeasonCreate')
                ->where('selectedCompetitionId', $competition->id));
    }

    public function test_competition_can_continue_to_season_page_and_season_can_be_saved(): void
    {
        $admin = User::firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.competitions.store'), [
            'name' => 'Liga Uji COSMIC',
            'short_name' => 'LUC',
            'type' => 'league',
            'organizer' => 'COSMIC',
            'description' => null,
            'logo_media_id' => null,
            'banner_media_id' => null,
            'website_url' => null,
            'status' => 'active',
            'continue_to_season' => true,
        ]);

        $competition = Competition::where('slug', 'liga-uji-cosmic')->firstOrFail();
        $response->assertRedirect(route('admin.competitions.seasons.create', ['competition' => $competition->id]));

        $this->actingAs($admin)->post(route('admin.competitions.seasons.save', $competition), [
            'name' => 'Musim 2027',
            'starts_at' => '2027-01-01',
            'ends_at' => '2027-12-31',
            'format' => 'league',
            'current_matchday' => 0,
            'total_matchdays' => 10,
            'max_participants' => 8,
            'win_points' => 3,
            'draw_points' => 1,
            'loss_points' => 0,
            'tie_breakers' => ['points', 'goal_difference', 'goals_for'],
            'regulations' => [],
            'is_featured' => false,
            'status' => 'draft',
            'from_create_page' => true,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.competitions.index'));

        $this->assertDatabaseHas('seasons', [
            'competition_id' => $competition->id,
            'slug' => 'musim-2027',
        ]);
    }

    private function competitionData(int $userId): array
    {
        return [
            'uuid' => fake()->uuid(),
            'name' => 'Kompetisi Awal',
            'short_name' => 'KA',
            'slug' => 'kompetisi-awal',
            'type' => 'league',
            'organizer' => 'COSMIC',
            'status' => 'active',
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }
}
