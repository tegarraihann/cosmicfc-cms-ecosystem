<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\GameMatch;
use App\Models\User;
use App\Services\MatchService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LiveMatchDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_live_match_is_separate_from_next_match(): void
    {
        [$cosmic, $opponent] = $this->clubs();

        $live = $this->match($cosmic, $opponent, [
            'slug' => 'cosmic-live',
            'status' => 'live',
            'home_score' => 1,
            'away_score' => 0,
            'kickoff_at' => now(),
        ]);
        $next = $this->match($cosmic, $opponent, [
            'slug' => 'cosmic-next',
            'status' => 'scheduled',
            'is_featured' => true,
            'kickoff_at' => now()->addWeek(),
        ]);

        app(MatchService::class)->forget();
        $data = app(MatchService::class)->publicData();

        $this->assertSame($live->slug, $data['live']['slug']);
        $this->assertSame($next->slug, $data['next']['slug']);
        $this->assertSame('live', $data['live']['status']);
        $this->assertNotContains($live->slug, $data['upcoming']->pluck('slug')->all());
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('matchData.live.slug', $live->slug)
            ->where('matchData.next.slug', $next->slug));
        $this->get(route('schedule'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('matchData.live.slug', $live->slug)
            ->where('matchData.next.slug', $next->slug));
    }

    public function test_private_live_match_is_not_exposed(): void
    {
        [$cosmic, $opponent] = $this->clubs();
        $this->match($cosmic, $opponent, [
            'slug' => 'private-live',
            'status' => 'live',
            'is_public' => false,
        ]);

        app(MatchService::class)->forget();

        $this->assertNull(app(MatchService::class)->publicData()['live']);
    }

    public function test_live_match_cannot_remain_featured_as_next_match(): void
    {
        [$cosmic, $opponent] = $this->clubs();
        $admin = User::firstOrFail();

        $this->actingAs($admin)->post(route('admin.matches.store'), [
            'slug' => 'live-featured-request',
            'season_id' => null,
            'match_type' => 'friendly',
            'matchday' => null,
            'round_name' => null,
            'home_club_id' => $cosmic->id,
            'away_club_id' => $opponent->id,
            'kickoff_at' => now()->format('Y-m-d H:i:s'),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => 'Aero Sport Center',
            'venue_address' => 'Pekanbaru',
            'status' => 'live',
            'home_score' => 0,
            'away_score' => 0,
            'count_for_standings' => false,
            'is_featured' => true,
            'is_public' => true,
            'cover_media_id' => null,
            'short_report' => null,
            'attendance' => null,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('matches', [
            'slug' => 'live-featured-request',
            'status' => 'live',
            'is_featured' => false,
            'is_public' => true,
        ]);
    }

    private function clubs(): array
    {
        return [
            Club::create($this->clubData('cosmic', 'COSMIC', 'CSM', true)),
            Club::create($this->clubData('rivals', 'Rivals', 'RVS')),
        ];
    }

    private function clubData(string $slug, string $name, string $shortName, bool $cosmic = false): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'slug' => $slug,
            'official_name' => $name,
            'display_name' => $name,
            'short_name' => $shortName,
            'type' => $cosmic ? 'internal' : 'opponent',
            'country' => 'Indonesia',
            'is_cosmic' => $cosmic,
            'status' => 'active',
        ];
    }

    private function match(Club $home, Club $away, array $overrides = []): GameMatch
    {
        return GameMatch::create(array_merge([
            'uuid' => (string) Str::uuid(),
            'slug' => (string) Str::uuid(),
            'match_type' => 'friendly',
            'home_club_id' => $home->id,
            'away_club_id' => $away->id,
            'kickoff_at' => now()->addDay(),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => 'Aero Sport Center',
            'venue_address' => 'Pekanbaru',
            'status' => 'scheduled',
            'is_featured' => false,
            'is_public' => true,
        ], $overrides));
    }
}
