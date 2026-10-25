<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Season;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerSlugWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        Season::create([
            'name' => 'Musim Pengujian',
            'slug' => 'musim-pengujian',
            'is_active' => true,
            'status' => 'ongoing',
        ]);
    }

    public function test_slug_from_hidden_or_stale_form_state_is_ignored_and_generated_uniquely(): void
    {
        $admin = User::firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.players.store'), $this->payload([
                'display_name' => 'Nama Sama',
                'slug' => 'slug-lama-dari-form',
                'shirt_number' => 1,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.players.store'), $this->payload([
                'display_name' => 'Nama Sama',
                'slug' => 'nama-sama',
                'shirt_number' => 2,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('players', ['display_name' => 'Nama Sama', 'slug' => 'nama-sama']);
        $this->assertDatabaseHas('players', ['display_name' => 'Nama Sama', 'slug' => 'nama-sama-2']);
        $this->assertDatabaseMissing('players', ['slug' => 'slug-lama-dari-form']);
    }

    public function test_edit_keeps_existing_slug_even_when_request_contains_another_slug(): void
    {
        $admin = User::firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.players.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $player = Player::firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.players.update', $player), $this->payload([
                'display_name' => 'Nama Tampilan Baru',
                'slug' => 'slug-yang-tidak-boleh-dipakai',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'display_name' => 'Nama Tampilan Baru',
            'slug' => 'pemain-uji',
        ]);
    }

    public function test_duplicate_shirt_number_uses_an_indonesian_validation_message(): void
    {
        $admin = User::firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.players.store'), $this->payload(['shirt_number' => 7]))
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)
            ->post(route('admin.players.store'), $this->payload([
                'display_name' => 'Pemain Kedua',
                'shirt_number' => 7,
            ]))
            ->assertSessionHasErrors([
                'shirt_number' => 'Nomor punggung sudah digunakan pemain lain.',
            ]);
    }

    private function payload(array $overrides = []): array
    {
        return [...[
            'full_name' => 'Pemain Uji Lengkap',
            'display_name' => 'Pemain Uji',
            'birth_date' => '2000-01-01',
            'height_cm' => 175,
            'dominant_foot' => 'Kanan',
            'hometown' => 'Pekanbaru',
            'biography' => '',
            'primary_media_id' => null,
            'image_focal_x' => 50,
            'image_focal_y' => 40,
            'status' => 'active',
            'shirt_number' => 10,
            'primary_position' => 'Forward',
            'squad_status' => 'active',
            'is_captain' => false,
            'is_featured' => false,
            'featured_order' => null,
            'appearances' => 0,
            'starts' => 0,
            'minutes' => 0,
            'goals' => 0,
            'assists' => 0,
            'clean_sheets' => 0,
            'yellow_cards' => 0,
            'red_cards' => 0,
            'rating' => null,
            'attendance_percent' => null,
        ], ...$overrides];
    }
}
