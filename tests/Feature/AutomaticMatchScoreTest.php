<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\GameMatch;
use App\Models\MatchAudit;
use App\Models\MatchEvent;
use App\Models\User;
use App\Services\MatchEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutomaticMatchScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_goals_are_synchronized_after_create_update_and_delete(): void
    {
        [$match, $home, $away] = $this->match();
        $service = app(MatchEventService::class);
        $event = $this->event($match, $home, 'goal');

        $service->synchronizeScore($match, null, 'test_create');
        $this->assertSame([1, 0], [$match->fresh()->home_score, $match->fresh()->away_score]);

        $event->update(['club_id' => $away->id]);
        $service->synchronizeScore($match, null, 'test_update');
        $this->assertSame([0, 1], [$match->fresh()->home_score, $match->fresh()->away_score]);

        $event->delete();
        $service->synchronizeScore($match, null, 'test_delete');
        $this->assertSame([0, 0], [$match->fresh()->home_score, $match->fresh()->away_score]);
        $this->assertTrue(MatchAudit::where('match_id', $match->id)->where('action', 'score_recalculated')->exists());
    }

    public function test_non_goal_events_do_not_change_score(): void
    {
        [$match, $home] = $this->match();
        $this->event($match, $home, 'yellow_card');

        app(MatchEventService::class)->synchronizeScore($match);

        $this->assertSame([0, 0], [$match->fresh()->home_score, $match->fresh()->away_score]);
    }

    public function test_shootout_goals_are_stored_separately_from_regular_score(): void
    {
        [$match, $home] = $this->match();
        $this->event($match, $home, 'goal', 'first_half');
        $this->event($match, $home, 'penalty_goal', 'shootout');

        app(MatchEventService::class)->synchronizeScore($match);
        $match->refresh();

        $this->assertSame(1, $match->home_score);
        $this->assertSame(1, $match->home_penalty_score);
        $this->assertNull($match->away_penalty_score);
    }

    public function test_manual_score_is_never_overwritten_by_events(): void
    {
        [$match, $home] = $this->match(['score_mode' => 'manual', 'home_score' => 3, 'away_score' => 0, 'manual_score_reason' => 'Hasil resmi menang walkover.']);
        $this->event($match, $home, 'goal');

        $changed = app(MatchEventService::class)->synchronizeScore($match);

        $this->assertFalse($changed);
        $this->assertSame([3, 0], [$match->fresh()->home_score, $match->fresh()->away_score]);
    }

    public function test_event_endpoint_immediately_updates_live_match_score(): void
    {
        [$match, $home] = $this->match();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.matches.events.store', $match), [
            'club_id' => $home->id, 'type' => 'goal', 'period' => 'first_half', 'minute' => 12,
            'added_minute' => 0, 'primary_player_name' => 'Pemain Uji', 'is_public' => false,
            'sort_order' => 0,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $match->fresh()->home_score);
    }

    private function match(array $changes = []): array
    {
        $home = $this->club('cosmic', true);
        $away = $this->club('lawan');
        $match = GameMatch::create(array_merge([
            'uuid' => (string) Str::uuid(), 'slug' => 'cosmic-vs-lawan', 'match_type' => 'sparing',
            'home_club_id' => $home->id, 'away_club_id' => $away->id, 'status' => 'live',
            'score_mode' => 'automatic', 'home_score' => 0, 'away_score' => 0,
            'count_for_standings' => false, 'is_public' => true,
        ], $changes));

        return [$match, $home, $away];
    }

    private function club(string $slug, bool $cosmic = false): Club
    {
        return Club::create([
            'uuid' => (string) Str::uuid(), 'slug' => $slug, 'official_name' => ucfirst($slug),
            'display_name' => ucfirst($slug), 'short_name' => strtoupper(substr($slug, 0, 6)),
            'type' => $cosmic ? 'internal' : 'opponent', 'is_cosmic' => $cosmic, 'status' => 'active',
        ]);
    }

    private function event(GameMatch $match, Club $club, string $type, string $period = 'first_half'): MatchEvent
    {
        return MatchEvent::create([
            'uuid' => (string) Str::uuid(), 'match_id' => $match->id, 'club_id' => $club->id,
            'type' => $type, 'period' => $period, 'minute' => 10, 'primary_player_name' => 'Pemain Uji',
            'is_score_event' => in_array($type, MatchEventService::SCORE_TYPES), 'is_public' => true,
        ]);
    }
}
