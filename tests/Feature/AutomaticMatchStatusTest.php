<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\GameMatch;
use App\Models\MatchAdminPresence;
use App\Models\MatchAudit;
use App\Models\MatchNotificationLog;
use App\Models\User;
use App\Notifications\MatchResultReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AutomaticMatchStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_due_scheduled_match_automatically_becomes_live(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 20:00:00', 'Asia/Jakarta');
        $match = $this->match([
            'kickoff_at' => '2026-11-05 20:00:00',
            'is_featured' => true,
        ]);
        Cache::put('matches:public', ['stale' => true], 600);

        $this->assertSame(0, Artisan::call('matches:sync-statuses'));

        $match->refresh();
        $this->assertSame('live', $match->status);
        $this->assertFalse($match->is_featured);
        $this->assertSame([0, 0], [$match->home_score, $match->away_score]);
        $this->assertFalse(Cache::has('matches:public'));
        $this->assertDatabaseHas('match_audits', [
            'match_id' => $match->id,
            'user_id' => null,
            'action' => 'status_automatically_started',
        ]);
    }

    public function test_future_match_is_not_started_early(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 19:59:00', 'Asia/Jakarta');
        $match = $this->match(['kickoff_at' => '2026-11-05 20:00:00']);

        Artisan::call('matches:sync-statuses');

        $this->assertSame('scheduled', $match->fresh()->status);
        $this->assertFalse(MatchAudit::where('match_id', $match->id)->exists());
    }

    public function test_exceptional_and_final_statuses_are_never_changed(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 20:00:00', 'Asia/Jakarta');

        foreach (['draft', 'postponed', 'cancelled', 'walkover', 'completed', 'live'] as $status) {
            $match = $this->match([
                'slug' => "status-{$status}",
                'status' => $status,
                'kickoff_at' => '2026-11-05 19:00:00',
                'home_score' => in_array($status, ['walkover', 'completed', 'live']) ? 1 : null,
                'away_score' => in_array($status, ['walkover', 'completed', 'live']) ? 0 : null,
            ]);

            Artisan::call('matches:sync-statuses');

            $this->assertSame($status, $match->fresh()->status);
        }
    }

    public function test_live_match_is_completed_after_expected_end(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 22:00:00', 'Asia/Jakarta');
        $match = $this->match([
            'status' => 'live',
            'kickoff_at' => '2026-11-05 20:00:00',
            'expected_end_at' => '2026-11-05 22:00:00',
            'home_score' => 0,
            'away_score' => 0,
        ]);

        Artisan::call('matches:sync-statuses');

        $match->refresh();
        $this->assertSame('completed', $match->status);
        $this->assertNotNull($match->result_finalized_at);
        $this->assertDatabaseHas('match_audits', ['match_id' => $match->id, 'action' => 'status_automatically_completed']);
    }

    public function test_reminder_is_sent_once_when_no_admin_is_active(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 21:50:00', 'Asia/Jakarta');
        Notification::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        config()->set('services.telegram.bot_token', 'secret-test-token');
        config()->set('services.telegram.chat_id', '123456');
        User::factory()->superAdmin()->create(['is_active' => true]);
        $match = $this->match([
            'status' => 'live',
            'expected_end_at' => '2026-11-05 22:00:00',
            'home_score' => 1,
            'away_score' => 0,
        ]);

        Artisan::call('matches:sync-statuses');
        Artisan::call('matches:sync-statuses');

        Notification::assertSentTimes(MatchResultReminderNotification::class, 1);
        Http::assertSentCount(1);
        $this->assertSame(2, MatchNotificationLog::where('match_id', $match->id)->where('status', 'sent')->count());
    }

    public function test_external_reminder_is_deferred_while_admin_is_active(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 21:50:00', 'Asia/Jakarta');
        Notification::fake();
        Http::fake();
        config()->set('services.telegram.bot_token', 'secret-test-token');
        config()->set('services.telegram.chat_id', '123456');
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $match = $this->match([
            'status' => 'live',
            'expected_end_at' => '2026-11-05 22:00:00',
            'home_score' => 1,
            'away_score' => 0,
        ]);
        MatchAdminPresence::create(['match_id' => $match->id, 'user_id' => $admin->id, 'area' => 'events', 'last_seen_at' => now()]);

        Artisan::call('matches:sync-statuses');

        Notification::assertNothingSent();
        Http::assertNothingSent();
        $this->assertDatabaseCount('match_notification_logs', 0);
    }

    public function test_admin_heartbeat_endpoint_records_presence(): void
    {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $match = $this->match();

        $this->actingAs($admin)
            ->postJson(route('admin.matches.heartbeat', $match), ['area' => 'events'])
            ->assertOk()
            ->assertJson(['recorded' => true]);

        $this->assertDatabaseHas('match_admin_presences', [
            'match_id' => $match->id,
            'user_id' => $admin->id,
            'area' => 'events',
        ]);
    }

    public function test_failed_telegram_delivery_does_not_store_bot_token(): void
    {
        CarbonImmutable::setTestNow('2026-11-05 21:50:00', 'Asia/Jakarta');
        Notification::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 500)]);
        config()->set('services.telegram.bot_token', 'very-secret-token');
        config()->set('services.telegram.chat_id', '123456');
        $match = $this->match([
            'status' => 'live',
            'expected_end_at' => '2026-11-05 22:00:00',
            'home_score' => 0,
            'away_score' => 0,
        ]);

        Artisan::call('matches:sync-statuses');

        $log = MatchNotificationLog::where('match_id', $match->id)->where('channel', 'telegram')->firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertStringNotContainsString('very-secret-token', (string) $log->error_summary);
    }

    private function match(array $changes = []): GameMatch
    {
        static $sequence = 0;
        $sequence++;
        $home = $this->club("cosmic-{$sequence}", true);
        $away = $this->club("opponent-{$sequence}");

        return GameMatch::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'slug' => "automatic-status-{$sequence}",
            'match_type' => 'sparing',
            'home_club_id' => $home->id,
            'away_club_id' => $away->id,
            'kickoff_at' => '2026-11-05 20:00:00',
            'duration_minutes' => 120,
            'timezone' => 'Asia/Jakarta',
            'status' => 'scheduled',
            'score_mode' => 'automatic',
            'count_for_standings' => false,
            'is_featured' => false,
            'is_public' => true,
        ], $changes));
    }

    private function club(string $slug, bool $cosmic = false): Club
    {
        return Club::create([
            'uuid' => (string) Str::uuid(),
            'slug' => $slug,
            'official_name' => Str::headline($slug),
            'display_name' => Str::headline($slug),
            'short_name' => Str::upper(Str::substr($slug, 0, 6)),
            'type' => $cosmic ? 'internal' : 'opponent',
            'is_cosmic' => $cosmic,
            'status' => 'active',
        ]);
    }
}
