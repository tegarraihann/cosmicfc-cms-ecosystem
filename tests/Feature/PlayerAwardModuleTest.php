<?php

namespace Tests\Feature;

use App\Models\AwardType;
use App\Models\GameMatch;
use App\Models\Media;
use App\Models\Player;
use App\Models\PlayerAward;
use App\Models\PlayerAwardAudit;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerAwardModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_existing_player_of_month_is_migrated_and_public(): void
    {
        $award = PlayerAward::where('type', 'player_of_month')->first();
        $this->assertSame('player-of-the-month', $award->awardType->slug);
        $this->assertSame('published', $award->status);
        $this->assertSame(5, $award->statistics_snapshot['goals']);
        $this->get(route('team'))->assertOk()->assertInertia(fn ($page) => $page->where('playerData.playerOfMonth.name', 'Aditya')->where('awardData.featured.player.name', 'Aditya'));
    }

    public function test_admin_can_create_monthly_award_with_snapshot_and_nominees(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $raka = Player::where('slug', 'raka')->first();
        $aditya = Player::where('slug', 'aditya')->first();
        $this->actingAs($admin)->post(route('admin.player-awards.store'), $this->payload(['player_id' => $raka->id, 'nominee_ids' => [$raka->id, $aditya->id]]))->assertSessionHasNoErrors();
        $award = PlayerAward::where('period_key', '2026-11')->first();
        $this->assertSame(2, $award->nominees()->count());
        $this->assertArrayHasKey('goals', $award->statistics_snapshot);
        $this->assertDatabaseHas('player_award_audits', ['player_award_id' => $award->id, 'action' => 'created']);
    }

    public function test_award_context_is_unique(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->post(route('admin.player-awards.store'), $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.player-awards.store'), $this->payload(['player_id' => Player::where('slug', 'raka')->value('id')]))->assertSessionHasErrors('period_key');
    }

    public function test_scheduled_award_requires_publication_time(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.player-awards.store'), $this->payload([
            'status' => 'scheduled',
            'published_at' => null,
        ]))->assertSessionHasErrors('published_at');
    }

    public function test_match_award_requires_match_and_valid_lineup_player(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $type = AwardType::where('slug', 'player-of-the-match')->first();
        $this->actingAs($admin)->post(route('admin.player-awards.store'), $this->payload(['award_type_id' => $type->id, 'period_key' => 'match-1', 'match_id' => null]))->assertSessionHasErrors('match_id');
        $match = GameMatch::where('slug', 'cosmic-vs-backyard-fc')->first();
        $aditya = Player::where('slug', 'aditya')->first();
        $match->lineups()->create(['club_id' => $match->home_club_id, 'player_id' => $aditya->id, 'role' => 'starter', 'display_order' => 1]);
        $this->actingAs($admin)->post(route('admin.player-awards.store'), $this->payload(['award_type_id' => $type->id, 'period_key' => 'match-'.$match->id, 'match_id' => $match->id, 'player_id' => Player::where('slug', 'raka')->value('id')]))->assertSessionHasErrors('player_id');
        $this->actingAs($admin)->post(route('admin.player-awards.store'), $this->payload(['award_type_id' => $type->id, 'period_key' => 'match-'.$match->id, 'match_id' => $match->id, 'player_id' => $aditya->id, 'status' => 'published', 'published_at' => now()->toDateTimeString()]))->assertSessionHasNoErrors();
        $this->assertSame('match-'.$match->id, PlayerAward::where('match_id', $match->id)->value('period_key'));
        $this->get(route('matches.show', $match->slug))->assertOk()->assertInertia(fn ($page) => $page->where('awardData.player.name', 'Aditya')->where('awardData.type', 'player-of-the-match'));
    }

    public function test_published_award_correction_requires_superadmin_and_reason(): void
    {
        $award = PlayerAward::where('status', 'published')->first();
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $this->actingAs($admin)->put(route('admin.player-awards.update', $award), $this->payload(['period_key' => '2026-10', 'status' => 'published']))->assertForbidden();
        $superadmin = User::where('email', 'superadmin@cosmic.test')->first();
        $this->actingAs($superadmin)->put(route('admin.player-awards.update', $award), $this->payload(['period_key' => '2026-10', 'status' => 'published']))->assertSessionHasErrors('correction_reason');
        $this->actingAs($superadmin)->put(route('admin.player-awards.update', $award), $this->payload(['period_key' => '2026-10', 'status' => 'published', 'correction_reason' => 'Perbaikan editorial resmi.']))->assertSessionHasNoErrors();
        $this->assertTrue(PlayerAwardAudit::where('player_award_id', $award->id)->where('action', 'corrected')->exists());
    }

    public function test_media_usage_and_archive_are_preserved(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->first();
        $media = Media::create(['uuid' => fake()->uuid(), 'disk' => 'public', 'directory' => 'awards', 'filename' => 'test.jpg', 'original_name' => 'test.jpg', 'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'size_bytes' => 100, 'checksum' => fake()->sha256(), 'title' => 'Award Photo', 'status' => 'active', 'uploaded_by' => $admin->id]);
        $this->actingAs($admin)->post(route('admin.player-awards.store'), $this->payload(['primary_media_id' => $media->id]))->assertSessionHasNoErrors();
        $award = PlayerAward::where('period_key', '2026-11')->first();
        $this->assertDatabaseHas('media_usages', ['media_id' => $media->id, 'usable_id' => $award->id, 'field_key' => 'primary_media_id']);
        $this->actingAs($admin)->post(route('admin.player-awards.archive', $award))->assertSessionHasNoErrors();
        $this->assertSame('archived', $award->fresh()->status);
        $this->assertDatabaseHas('player_awards', ['id' => $award->id]);
    }

    public function test_superadmin_can_manage_categories_without_entering_technical_fields(): void
    {
        $superadmin = User::where('email', 'superadmin@cosmic.test')->firstOrFail();

        $this->actingAs($superadmin)->post(route('admin.player-awards.types.save'), [
            'name' => 'Best Goalkeeper',
            'period_type' => 'season',
            'description' => 'Penjaga gawang terbaik selama satu musim.',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $type = AwardType::where('slug', 'best-goalkeeper')->firstOrFail();
        $originalSlug = $type->slug;
        $this->actingAs($superadmin)->post(route('admin.player-awards.types.save'), [
            'id' => $type->id,
            'name' => 'Kiper Terbaik',
            'period_type' => 'season',
            'description' => 'Kategori diperbarui.',
            'is_active' => false,
        ])->assertSessionHasNoErrors();

        $this->assertSame($originalSlug, $type->fresh()->slug);
        $this->assertFalse($type->fresh()->is_active);
        $this->actingAs($superadmin)->get(route('admin.player-awards.index'))->assertOk()->assertInertia(
            fn ($page) => $page
                ->where('types', fn ($types) => collect($types)->doesntContain('id', $type->id))
                ->where('categoryTypes', fn ($types) => collect($types)->contains(fn ($item) => $item['id'] === $type->id && $item['is_active'] === false)),
        );
    }

    private function payload(array $changes = []): array
    {
        $season = Season::where('is_featured', true)->first();

        return array_merge(['award_type_id' => AwardType::where('slug', 'player-of-the-month')->value('id'), 'player_id' => Player::where('slug', 'aditya')->value('id'), 'season_id' => $season->id, 'match_id' => null, 'period_key' => '2026-11', 'title' => 'Player of the Month', 'editorial' => 'Performa konsisten sepanjang periode penilaian.', 'quote' => 'Kami terus berkembang bersama.', 'primary_media_id' => null, 'status' => 'draft', 'is_featured' => false, 'published_at' => null, 'nominee_ids' => [], 'correction_reason' => ''], $changes);
    }
}
