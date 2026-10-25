<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlayerMediaPickerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_player_photo_is_stored_in_media_library_and_can_be_assigned(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $upload = $this->actingAs($admin)->post(route('admin.media.store'), [
            'photo' => UploadedFile::fake()->image('player.jpg', 900, 1200),
            'folder' => 'players',
        ]);

        $upload->assertCreated()->assertJsonPath('folder', 'players');
        $media = Media::firstOrFail();

        $this->actingAs($admin)->post(route('admin.players.store'), [
            'full_name' => 'Pemain Media COSMIC',
            'display_name' => 'Pemain Media',
            'birth_date' => '2000-01-01',
            'height_cm' => 175,
            'dominant_foot' => 'Kanan',
            'hometown' => 'Pekanbaru',
            'biography' => 'Profil pemain untuk pengujian foto.',
            'primary_media_id' => $media->id,
            'image_focal_x' => 50,
            'image_focal_y' => 35,
            'status' => 'active',
            'shirt_number' => 88,
            'primary_position' => 'Forward',
            'is_captain' => false,
            'is_featured' => false,
            'featured_order' => null,
        ])->assertSessionHasNoErrors();

        $player = Player::where('display_name', 'Pemain Media')->firstOrFail();
        $this->assertSame($media->id, $player->primary_media_id);
        $this->assertDatabaseHas('media_usages', [
            'media_id' => $media->id,
            'usable_type' => Player::class,
            'usable_id' => $player->id,
            'field_key' => 'primary_media_id',
        ]);
        Storage::disk('public')->assertExists("{$media->directory}/{$media->filename}");
    }

    public function test_player_page_exposes_media_permissions_to_the_picker(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.players.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Players')
                ->where('can.browse_media', true)
                ->where('can.upload_media', true));
    }
}
