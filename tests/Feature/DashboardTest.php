<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\GameMatch;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_uses_live_statistics_and_permission_aware_shortcuts(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $activePlayers = Player::where('status', 'active')->count();
        $upcomingMatches = GameMatch::whereIn('status', ['scheduled', 'live'])->where('kickoff_at', '>=', now())->count();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.0.value', $activePlayers)
                ->where('stats.1.value', $upcomingMatches)
                ->has('shortcuts', 5)
                ->where('shortcuts.0.label', 'Tambah Pertandingan')
                ->where('shortcuts.1.label', 'Tambah Pemain'));
    }

    public function test_dashboard_exposes_normalized_recent_activity(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('activities')
                ->has('stats', 4));
    }
}
