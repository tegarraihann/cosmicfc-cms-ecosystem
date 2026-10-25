<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\Staff;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaffMediaPickerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_photo_upload_is_stored_in_media_library_and_can_be_assigned(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $upload = $this->actingAs($admin)->post(route('admin.media.store'), [
            'photo' => UploadedFile::fake()->image('official.jpg', 900, 1200),
            'folder' => 'staff',
        ]);

        $upload->assertCreated()->assertJsonPath('folder', 'staff');
        $media = Media::firstOrFail();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'full_name' => 'Official COSMIC',
            'display_name' => 'Official',
            'hometown' => 'Pekanbaru',
            'biography' => 'Official klub.',
            'primary_media_id' => $media->id,
            'image_focal_x' => 50,
            'image_focal_y' => 35,
            'status' => 'active',
            'role_title' => 'Team Manager',
            'category' => 'official',
            'short_description' => 'Mengelola operasional tim.',
            'badge' => 'Official',
            'starts_at' => null,
            'ends_at' => null,
            'is_primary_founder' => false,
            'is_featured' => false,
            'featured_order' => null,
            'is_public' => true,
            'assignment_status' => 'active',
        ])->assertSessionHasNoErrors();

        $staff = Staff::firstOrFail();
        $this->assertSame($media->id, $staff->primary_media_id);
        $this->assertDatabaseHas('media_usages', [
            'media_id' => $media->id,
            'usable_type' => Staff::class,
            'usable_id' => $staff->id,
            'field_key' => 'primary_media_id',
        ]);
        Storage::disk('public')->assertExists("{$media->directory}/{$media->filename}");
    }

    public function test_staff_page_exposes_media_permissions_to_the_picker(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.staff.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Staff')
                ->where('can.browse_media', true)
                ->where('can.upload_media', true));
    }
}
