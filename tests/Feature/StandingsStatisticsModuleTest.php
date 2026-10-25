<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Season;
use App\Models\StandingAdjustment;
use App\Models\StandingSnapshot;
use App\Models\StatisticRecalculationLog;
use App\Models\User;
use App\Services\StandingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandingsStatisticsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeded_results_generate_correct_standings(): void
    {
        $season = Season::where('is_featured', true)->first();
        $cosmic = Club::where('slug', 'cosmic')->first();
        $row = StandingSnapshot::where('season_id', $season->id)->where('club_id', $cosmic->id)->latest('matchday')->first();
        $this->assertSame(3, $row->played);
        $this->assertSame(2, $row->won);
        $this->assertSame(1, $row->drawn);
        $this->assertSame(8, $row->goals_for);
        $this->assertSame(3, $row->goals_against);
        $this->assertSame(7, $row->points);
        $this->assertSame(1, $row->position);
    }

    public function test_public_pages_receive_dynamic_standings(): void
    {
        $this->get(route('league'))->assertOk()->assertInertia(fn ($page) => $page->has('standingsData.standings', 7)->where('standingsData.cosmic.points', 7));
        $this->get(route('schedule'))->assertOk()->assertInertia(fn ($page) => $page->has('standingsData.compact', 5));
    }

    public function test_admin_can_apply_adjustment_directly_and_publish_updated_standings(): void
    {
        $season = Season::where('is_featured', true)->first();
        $cosmic = Club::where('slug', 'cosmic')->first();
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->post(route('admin.standings.recalculate', $season), ['publish' => false])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.standings.adjustments.store', $season), ['club_id' => $cosmic->id, 'points' => -2, 'reason' => 'Sanksi administratif', 'reference' => 'SK-01', 'effective_matchday' => 2])->assertSessionHasNoErrors();
        $adjustment = StandingAdjustment::latest()->first();
        $this->assertTrue($adjustment->fresh()->is_active);
        $this->assertSame(5, StandingSnapshot::where('season_id', $season->id)->where('club_id', $cosmic->id)->latest('matchday')->value('points'));
        $this->assertDatabaseHas('statistic_recalculation_logs', ['season_id' => $season->id, 'trigger' => 'adjustment_created', 'status' => 'completed']);
        $this->get(route('league'))->assertOk()->assertInertia(fn ($page) => $page->where('standingsData.cosmic.points', 5));
    }

    public function test_recalculation_is_idempotent_and_logged(): void
    {
        $season = Season::where('is_featured', true)->first();
        $service = app(StandingsService::class);
        $before = StandingSnapshot::count();
        $service->recalculate($season, null, 'test', true);
        $service->recalculate($season, null, 'test', true);
        $this->assertSame($before, StandingSnapshot::count());
        $this->assertSame(2, StatisticRecalculationLog::where('trigger', 'test')->where('status', 'completed')->count());
    }

    public function test_export_is_protected_and_downloadable(): void
    {
        $season = Season::where('is_featured', true)->first();
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->get(route('admin.standings.export', $season))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
