<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Club;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClubMediaPickerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_club_logo_is_stored_in_media_library_and_assigned_to_club(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $upload = $this->actingAs($admin)->post(route('admin.media.store'), [
            'photo' => UploadedFile::fake()->image('logo-rivals.png', 600, 600),
            'folder' => 'clubs',
        ]);

        $upload->assertCreated()->assertJsonPath('folder', 'clubs');
        $logo = Media::firstOrFail();

        $this->actingAs($admin)->post(route('admin.clubs.store'), [
            'official_name' => 'Rivals Mini Soccer Club',
            'display_name' => 'Rivals',
            'short_name' => 'RVS',
            'type' => 'opponent',
            'city' => 'Pekanbaru',
            'country' => 'Indonesia',
            'founded_year' => 2024,
            'primary_media_id' => $logo->id,
            'alternate_media_id' => null,
            'primary_color' => '#112233',
            'secondary_color' => '#FFFFFF',
            'website_url' => null,
            'instagram_url' => null,
            'contact_email' => null,
            'default_venue' => null,
            'is_cosmic' => false,
            'status' => 'active',
            'aliases' => [],
        ])->assertSessionHasNoErrors();

        $club = Club::firstOrFail();
        $this->assertSame($logo->id, $club->primary_media_id);
        $this->assertDatabaseHas('media_usages', [
            'media_id' => $logo->id,
            'usable_type' => Club::class,
            'usable_id' => $club->id,
            'field_key' => 'primary_media_id',
        ]);
        Storage::disk('public')->assertExists("{$logo->directory}/{$logo->filename}");
    }

    public function test_club_page_exposes_media_permissions_and_logo_urls(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.clubs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Clubs')
                ->where('can.browse_media', true)
                ->where('can.upload_media', true));
    }
}
