<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Competition;
use App\Models\GameMatch;
use App\Models\MatchMvpAssessment;
use App\Models\Player;
use App\Models\PlayerAward;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MatchMvpWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private GameMatch $match;

    private Player $firstPlayer;

    private Player $secondPlayer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'superadmin@cosmic.test')->firstOrFail();

        $competition = Competition::create([
            'uuid' => Str::uuid(), 'slug' => 'liga-uji', 'name' => 'Liga Uji',
            'short_name' => 'LU', 'type' => 'league', 'status' => 'active',
        ]);
        $season = Season::create([
            'competition_id' => $competition->id, 'name' => 'Musim Uji 2026',
            'slug' => 'musim-uji-2026', 'format' => 'league', 'status' => 'completed',
        ]);
        $home = $this->club('cosmic-uji', 'COSMIC Uji', true);
        $away = $this->club('lawan-uji', 'Lawan Uji');
        $this->firstPlayer = $this->player('pemain-satu', 'Pemain Satu');
        $this->secondPlayer = $this->player('pemain-dua', 'Pemain Dua');

        $this->match = GameMatch::create([
            'uuid' => Str::uuid(), 'slug' => 'cosmic-uji-vs-lawan-uji',
            'season_id' => $season->id, 'match_type' => 'competitive',
            'home_club_id' => $home->id, 'away_club_id' => $away->id,
            'kickoff_at' => now()->subDay(), 'status' => 'completed',
            'home_score' => 2, 'away_score' => 1, 'is_public' => true,
        ]);
        $this->match->lineups()->createMany([
            ['club_id' => $home->id, 'player_id' => $this->firstPlayer->id, 'role' => 'starter', 'position' => 'Forward', 'shirt_number' => 9, 'display_order' => 1],
            ['club_id' => $home->id, 'player_id' => $this->secondPlayer->id, 'role' => 'starter', 'position' => 'Midfielder', 'shirt_number' => 8, 'display_order' => 2],
        ]);
        $this->match->events()->create([
            'uuid' => Str::uuid(), 'club_id' => $home->id, 'type' => 'goal',
            'period' => 'second_half', 'minute' => 55,
            'primary_player_id' => $this->firstPlayer->id,
            'secondary_player_id' => $this->secondPlayer->id,
            'is_score_event' => true, 'is_public' => true,
        ]);
    }

    public function test_mvp_page_only_offers_players_from_the_match_lineup(): void
    {
        $outside = $this->player('pemain-luar', 'Pemain Luar');

        $this->actingAs($this->admin)->get(route('admin.matches.mvp.show', $this->match))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/MatchMvp')
                ->has('candidates', 2)
                ->where('candidates.0.name', 'Pemain Satu')
                ->where('match.ready', true)
                ->where('candidates', fn ($items) => collect($items)->doesntContain('id', $outside->id)));
    }

    public function test_admin_can_score_candidates_with_weighted_result(): void
    {
        $this->actingAs($this->admin)->put(route('admin.matches.mvp.assessments', $this->match), [
            'candidates' => [$this->assessment($this->firstPlayer->id, [
                'result_impact' => 5, 'position_performance' => 4, 'consistency' => 4,
                'crucial_moments' => 5, 'discipline_teamwork' => 3,
            ])],
        ])->assertSessionHasNoErrors();

        $assessment = MatchMvpAssessment::firstOrFail();
        $this->assertSame('87.00', $assessment->total_score);
        $this->assertSame(1, $assessment->evidence_snapshot['goals']);
    }

    public function test_player_outside_lineup_cannot_be_assessed(): void
    {
        $outside = $this->player('pemain-luar', 'Pemain Luar');

        $this->actingAs($this->admin)->put(route('admin.matches.mvp.assessments', $this->match), [
            'candidates' => [$this->assessment($outside->id)],
        ])->assertSessionHasErrors('candidates');
    }

    public function test_non_top_winner_requires_reason_and_published_award_appears_publicly(): void
    {
        $this->actingAs($this->admin)->put(route('admin.matches.mvp.assessments', $this->match), [
            'candidates' => [
                $this->assessment($this->firstPlayer->id, ['result_impact' => 5]),
                $this->assessment($this->secondPlayer->id, ['result_impact' => 2]),
            ],
        ])->assertSessionHasNoErrors();

        $decision = [
            'player_id' => $this->secondPlayer->id,
            'title' => 'MVP Pertandingan',
            'editorial' => 'Menjadi pengatur tempo dan pembeda utama sepanjang pertandingan.',
            'status' => 'published',
        ];
        $this->actingAs($this->admin)->post(route('admin.matches.mvp.decision', $this->match), $decision)
            ->assertSessionHasErrors('decision_reason');

        $this->actingAs($this->admin)->post(route('admin.matches.mvp.decision', $this->match), [
            ...$decision,
            'decision_reason' => 'Kontribusi tanpa bola dan kepemimpinannya paling menentukan.',
        ])->assertSessionHasNoErrors();

        $award = PlayerAward::where('match_id', $this->match->id)->firstOrFail();
        $this->assertSame('published', $award->status);
        $this->assertSame($this->secondPlayer->id, $award->player_id);
        $this->assertSame('MVP Pertandingan', $award->title);
        $this->assertCount(2, $award->nominees);
        $this->get(route('matches.show', $this->match->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('awardData.player.name', 'Pemain Dua')
                ->where('awardData.title', 'MVP Pertandingan')
                ->where('awardData.typeName', 'Player of the Match')
                ->where('awardData.statistics.assists', 1));
    }

    public function test_sparing_mvp_can_be_published_without_season(): void
    {
        $this->match->update(['season_id' => null, 'match_type' => 'sparing']);
        $this->actingAs($this->admin)->put(route('admin.matches.mvp.assessments', $this->match), [
            'candidates' => [$this->assessment($this->firstPlayer->id)],
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('admin.matches.mvp.decision', $this->match), [
            'player_id' => $this->firstPlayer->id,
            'title' => 'MVP Sparing',
            'editorial' => 'Menjadi pemain paling berpengaruh sepanjang pertandingan sparing.',
            'status' => 'published',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('player_awards', [
            'match_id' => $this->match->id,
            'season_id' => null,
            'player_id' => $this->firstPlayer->id,
            'status' => 'published',
        ]);
    }

    private function club(string $slug, string $name, bool $cosmic = false): Club
    {
        return Club::create([
            'uuid' => Str::uuid(), 'slug' => $slug, 'official_name' => $name,
            'display_name' => $name, 'short_name' => strtoupper(substr($slug, 0, 3)),
            'type' => $cosmic ? 'internal' : 'opponent', 'is_cosmic' => $cosmic,
            'status' => 'active',
        ]);
    }

    private function player(string $slug, string $name): Player
    {
        return Player::create([
            'uuid' => Str::uuid(), 'slug' => $slug, 'name' => $name,
            'full_name' => $name, 'display_name' => $name,
            'position' => 'Midfielder', 'status' => 'active',
        ]);
    }

    private function assessment(int $playerId, array $changes = []): array
    {
        return array_merge([
            'player_id' => $playerId, 'result_impact' => 4,
            'position_performance' => 4, 'consistency' => 4,
            'crucial_moments' => 4, 'discipline_teamwork' => 4,
            'assessment_note' => 'Tampil konsisten dan memberi kontribusi penting.',
        ], $changes);
    }
}
