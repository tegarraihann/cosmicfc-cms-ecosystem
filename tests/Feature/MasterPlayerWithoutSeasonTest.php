<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Player;
use App\Models\Season;
use App\Models\User;
use App\Services\PlayerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MasterPlayerWithoutSeasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_master_can_be_managed_and_published_without_an_active_season(): void
    {
        $this->seed(DatabaseSeeder::class);
        Season::query()->update(['is_active' => false]);
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.players.store'), [
            'full_name' => 'Pemain Tanpa Musim',
            'display_name' => 'Pemain Utama',
            'birth_date' => '2000-01-01',
            'height_cm' => 175,
            'dominant_foot' => 'Kanan',
            'hometown' => 'Pekanbaru',
            'biography' => 'Profil pemain master.',
            'primary_media_id' => null,
            'image_focal_x' => 50,
            'image_focal_y' => 40,
            'status' => 'active',
            'shirt_number' => 10,
            'primary_position' => 'Forward',
            'is_captain' => true,
            'is_featured' => true,
            'featured_order' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('players', [
            'display_name' => 'Pemain Utama',
            'number' => 10,
            'position' => 'Forward',
            'is_captain' => true,
            'is_featured' => true,
        ]);
        $this->assertDatabaseCount('player_season', 0);

        $this->actingAs($admin)->get(route('admin.players.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Players')
                ->where('season', null)
                ->has('players.data', 1));

        Cache::forget('players:public');
        $public = app(PlayerService::class)->publicData();
        $this->assertSame('Pemain Utama', $public['players'][0]['name']);
        $this->assertSame('Pemain Utama', $public['captain']['name']);
        $this->assertSame('Pemain Utama', $public['featured'][0]['name']);
        $this->assertNull($public['season']);
    }

    public function test_shirt_number_is_unique_in_the_player_master(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        Player::create([
            'uuid' => fake()->uuid(),
            'slug' => 'pemain-pertama',
            'name' => 'Pemain Pertama',
            'full_name' => 'Pemain Pertama',
            'display_name' => 'Pemain Pertama',
            'number' => 7,
            'position' => 'Midfielder',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.players.store'), [
            'full_name' => 'Pemain Kedua',
            'display_name' => 'Pemain Kedua',
            'image_focal_x' => 50,
            'image_focal_y' => 40,
            'status' => 'active',
            'shirt_number' => 7,
            'primary_position' => 'Forward',
            'is_captain' => false,
            'is_featured' => false,
        ])->assertSessionHasErrors([
            'shirt_number' => 'Nomor punggung sudah digunakan pemain lain.',
        ]);
    }
}
