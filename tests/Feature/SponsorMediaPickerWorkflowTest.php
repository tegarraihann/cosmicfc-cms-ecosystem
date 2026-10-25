<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\Sponsor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SponsorMediaPickerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_logo_is_stored_in_media_library_and_can_be_assigned_to_sponsor(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $upload = $this->actingAs($admin)->post(route('admin.media.store'), [
            'photo' => UploadedFile::fake()->image('sponsor-logo.png', 800, 800),
            'folder' => 'sponsors',
        ]);

        $upload->assertCreated()->assertJsonPath('folder', 'sponsors');
        $media = Media::firstOrFail();

        $this->actingAs($admin)->post(route('admin.sponsors.store'), [
            'name' => 'Sponsor Pengujian',
            'category' => 'main',
            'description' => 'Sponsor untuk pengujian alur Media Library.',
            'logo_media_id' => $media->id,
            'website_url' => null,
            'instagram_url' => null,
            'contact_name' => null,
            'contact_email' => null,
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $sponsor = Sponsor::firstOrFail();

        $this->assertSame($media->id, $sponsor->logo_media_id);
        $this->assertDatabaseHas('media_usages', [
            'media_id' => $media->id,
            'usable_type' => Sponsor::class,
            'usable_id' => $sponsor->id,
            'field_key' => 'logo_media_id',
        ]);
        Storage::disk('public')->assertExists("{$media->directory}/{$media->filename}");
    }

    public function test_sponsor_page_exposes_media_permissions_to_the_picker(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.sponsors.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Sponsors')
                ->where('can.browse_media', true)
                ->where('can.upload_media', true));
    }
}
