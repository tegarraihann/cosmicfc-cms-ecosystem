<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\MatchEvent;
use App\Models\MatchEventAudit;
use App\Models\Player;
use App\Models\User;
use App\Services\PlayerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchEventModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeded_events_are_reconciled_and_public(): void
    {
        $match = GameMatch::where('slug', 'cosmic-vs-backyard-fc')->first();
        $this->assertCount(6, $match->events);
        $this->get(route('matches.show', $match->slug))->assertOk()->assertInertia(fn ($page) => $page->has('matchData.events', 6)->where('matchData.reconciliation.synced', true));
    }

    public function test_superadmin_can_add_event_to_completed_match_with_reason(): void
    {
        $admin = User::where('email', 'superadmin@cosmic.test')->first();
        $match = GameMatch::where('slug', 'cosmic-vs-backyard-fc')->first();
        $this->actingAs($admin)->post(route('admin.matches.events.store', $match), $this->payload($match, ['type' => 'yellow_card', 'correction_reason' => 'Melengkapi laporan resmi.']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('match_events', ['match_id' => $match->id, 'type' => 'yellow_card', 'minute' => 35]);
        $this->assertDatabaseHas('match_event_audits', ['match_id' => $match->id, 'action' => 'created']);
    }

    public function test_regular_admin_cannot_correct_completed_match(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $match = GameMatch::where('slug', 'cosmic-vs-backyard-fc')->first();
        $this->actingAs($admin)->post(route('admin.matches.events.store', $match), $this->payload($match, ['correction_reason' => 'Koreksi']))->assertForbidden();
    }

    public function test_event_club_must_belong_to_match(): void
    {
        $admin = User::where('email', 'superadmin@cosmic.test')->first();
        $match = GameMatch::where('slug', 'cosmic-vs-rivals')->first();
        $other = GameMatch::where('slug', 'cosmic-vs-wfc')->first()->away_club_id;
        $this->actingAs($admin)->post(route('admin.matches.events.store', $match), $this->payload($match, ['club_id' => $other]))->assertSessionHasErrors('club_id');
    }

    public function test_substitution_requires_second_player(): void
    {
        $admin = User::where('email', 'superadmin@cosmic.test')->first();
        $match = GameMatch::where('slug', 'cosmic-vs-rivals')->first();
        $this->actingAs($admin)->post(route('admin.matches.events.store', $match), $this->payload($match, ['type' => 'substitution']))->assertSessionHasErrors('secondary_player_id');
    }

    public function test_completed_event_update_is_audited(): void
    {
        $admin = User::where('email', 'superadmin@cosmic.test')->first();
        $event = MatchEvent::first();
        $this->actingAs($admin)->put(route('admin.matches.events.update', [$event->match_id, $event]), $this->payload($event->match, ['minute' => 13, 'correction_reason' => 'Menit resmi diperbarui.']))->assertSessionHasNoErrors();
        $this->assertSame(13, $event->fresh()->minute);
        $this->assertTrue(MatchEventAudit::where('match_event_id', $event->id)->where('action', 'corrected')->exists());
    }

    public function test_player_leaders_use_published_match_events(): void
    {
        $data = app(PlayerService::class)->publicData();
        $scorer = collect($data['players'])->firstWhere('id', 'aditya');
        $this->assertSame(5, $scorer['stats']['goals']);
        $this->assertSame(0, $scorer['stats']['assists']);
        $this->assertSame('aditya', $data['topPlayers'][0]['id']);
        $this->assertSame('raka', $data['topPlayers'][1]['id']);
    }

    private function payload(GameMatch $match, array $changes = []): array
    {
        return array_merge(['club_id' => $match->home_club_id, 'type' => 'goal', 'period' => 'first_half', 'minute' => 35, 'added_minute' => 0, 'primary_player_id' => Player::where('slug', 'aditya')->value('id'), 'primary_player_name' => '', 'secondary_player_id' => null, 'secondary_player_name' => '', 'note' => '', 'is_public' => true, 'sort_order' => 1, 'correction_reason' => ''], $changes);
    }
}
