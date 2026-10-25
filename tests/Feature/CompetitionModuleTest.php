<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Competition;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeded_competition_is_available_to_admin_and_public(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->get(route('admin.competitions.index'))->assertOk()->assertInertia(fn ($p) => $p->component('Admin/Competitions')->has('competitions.data', 1));
        $this->get(route('league'))->assertOk()->assertInertia(fn ($p) => $p->where('competitionData.competition.name', 'COSMIC League')->has('competitionData.participants', 7));
    }

    public function test_admin_can_create_competition_and_season(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $payload = $this->competitionPayload();
        unset($payload['slug']);
        $this->actingAs($admin)->post(route('admin.competitions.store'), $payload)->assertSessionHasNoErrors();
        $competition = Competition::where('slug', 'weekend-series')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.competitions.seasons.save', ['competition' => $competition]), $this->seasonPayload())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('seasons', ['competition_id' => $competition->id, 'slug' => 'weekend-series-2027']);
    }

    public function test_competition_slug_is_preserved_when_name_is_edited(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $competition = Competition::firstOrFail();
        $originalSlug = $competition->slug;
        $payload = $this->competitionPayload();
        unset($payload['slug']);
        $payload['name'] = 'Nama Kompetisi Baru';

        $this->actingAs($admin)->put(route('admin.competitions.update', $competition), $payload)->assertSessionHasNoErrors();

        $this->assertSame($originalSlug, $competition->fresh()->slug);
    }

    public function test_featured_season_is_unique(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $competition = Competition::first();
        $this->actingAs($admin)->post(route('admin.competitions.seasons.save', ['competition' => $competition]), $this->seasonPayload(['slug' => 'season-2027', 'name' => 'Season 2027', 'is_featured' => true]))->assertSessionHasNoErrors();
        $this->assertSame(1, Season::where('is_featured', true)->count());
        $this->assertTrue(Season::where('slug', 'season-2027')->first()->is_featured);
    }

    public function test_season_slug_is_preserved_when_name_is_edited(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $season = Season::where('slug', 'cosmic-league-2026')->firstOrFail();
        $originalSlug = $season->slug;
        $payload = $this->seasonPayload([
            'name' => 'Musim COSMIC Terbaru',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
            'current_matchday' => 4,
            'total_matchdays' => 7,
            'status' => 'ongoing',
        ]);
        unset($payload['slug']);

        $this->actingAs($admin)->post(route('admin.competitions.seasons.save', ['competition' => $season->competition_id, 'season' => $season]), $payload)->assertSessionHasNoErrors();

        $this->assertSame($originalSlug, $season->fresh()->slug);
    }

    public function test_invalid_dates_and_matchday_are_rejected(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $competition = Competition::first();
        $this->actingAs($admin)->post(route('admin.competitions.seasons.save', ['competition' => $competition]), $this->seasonPayload(['ends_at' => '2026-01-01', 'starts_at' => '2026-12-01']))->assertSessionHasErrors('ends_at');
        $this->actingAs($admin)->post(route('admin.competitions.seasons.save', ['competition' => $competition]), $this->seasonPayload(['current_matchday' => 8, 'total_matchdays' => 7]))->assertSessionHasErrors('current_matchday');
    }

    public function test_participants_are_unique_and_locked_after_scheduling(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $season = Season::where('slug', 'cosmic-league-2026')->first();
        $club = Club::first();
        $this->actingAs($admin)->put(route('admin.competitions.participants.sync', $season), ['participants' => [['club_id' => $club->id, 'seed' => 1, 'display_order' => 1, 'status' => 'active']]])->assertSessionHasErrors('participants');
    }

    public function test_participant_details_are_assigned_automatically(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $competition = Competition::firstOrFail();
        $clubs = Club::take(2)->get();

        $this->actingAs($admin)->post(
            route('admin.competitions.seasons.save', ['competition' => $competition]),
            $this->seasonPayload(['name' => 'Musim Pendaftaran', 'status' => 'registration']),
        )->assertSessionHasNoErrors();

        $season = Season::where('name', 'Musim Pendaftaran')->firstOrFail();
        $this->actingAs($admin)->put(
            route('admin.competitions.participants.sync', $season),
            ['participants' => $clubs->map(fn ($club) => ['club_id' => $club->id])->all()],
        )->assertSessionHasNoErrors();

        foreach ($clubs as $index => $club) {
            $this->assertDatabaseHas('competition_participants', [
                'season_id' => $season->id,
                'club_id' => $club->id,
                'seed' => null,
                'group_name' => null,
                'display_order' => $index + 1,
                'status' => 'active',
            ]);
        }
    }

    public function test_regular_admin_cannot_delete_competition(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->delete(route('admin.competitions.destroy', Competition::first()))->assertForbidden();
    }

    private function competitionPayload(): array
    {
        return ['name' => 'Weekend Series', 'short_name' => 'WKS', 'slug' => 'weekend-series', 'type' => 'friendly_series', 'organizer' => 'COSMIC', 'description' => 'Series akhir pekan', 'logo_media_id' => null, 'banner_media_id' => null, 'website_url' => null, 'status' => 'active'];
    }

    private function seasonPayload(array $overrides = []): array
    {
        return array_merge(['name' => 'Weekend Series 2027', 'slug' => 'weekend-series-2027', 'starts_at' => '2027-01-01', 'ends_at' => '2027-12-31', 'format' => 'league', 'current_matchday' => 0, 'total_matchdays' => 7, 'max_participants' => 8, 'win_points' => 3, 'draw_points' => 1, 'loss_points' => 0, 'tie_breakers' => ['points', 'goal_difference'], 'regulations' => [['title' => 'Format', 'body' => 'Format liga.', 'active' => true]], 'is_featured' => false, 'status' => 'registration'], $overrides);
    }
}
