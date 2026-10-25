<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\PageSection;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
    }

    public function test_admin_can_open_media_library(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $this->actingAs($admin)->get(route('admin.media.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/MediaLibrary')->has('media.data')->where('can.upload', true)->where('can.restore', false));
    }

    public function test_admin_can_upload_image_with_metadata(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $response = $this->actingAs($admin)->post(route('admin.media.store'), ['photo' => UploadedFile::fake()->image('squad.jpg', 1200, 800)->size(500), 'folder' => 'players']);
        $response->assertCreated()->assertJsonPath('folder', 'players')->assertJsonPath('duplicate', false);
        $media = Media::firstOrFail();
        Storage::disk('public')->assertExists("{$media->directory}/{$media->filename}");
        $this->assertSame(1200, $media->width);
        $this->assertDatabaseHas('media_audits', ['media_id' => $media->id, 'action' => 'uploaded']);
    }

    public function test_duplicate_upload_reuses_existing_media(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $file = UploadedFile::fake()->image('same.jpg', 300, 300);
        $this->actingAs($admin)->post(route('admin.media.store'), ['photo' => $file, 'folder' => 'general'])->assertCreated();
        $this->actingAs($admin)->post(route('admin.media.store'), ['photo' => UploadedFile::fake()->createWithContent('same.jpg', $file->getContent()), 'folder' => 'general'])->assertOk()->assertJsonPath('duplicate', true);
        $this->assertSame(1, Media::count());
    }

    public function test_used_media_cannot_be_deleted(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $media = $this->upload($admin);
        $section = PageSection::firstOrFail();
        MediaUsage::create(['media_id' => $media->id, 'usable_type' => $section->getMorphClass(), 'usable_id' => $section->id, 'field_key' => 'heroImage']);
        $this->actingAs($admin)->delete(route('admin.media.destroy', $media))->assertSessionHasErrors('media');
        $this->assertFalse($media->fresh()->trashed());
    }

    public function test_unused_media_can_be_deleted_and_superadmin_can_restore_it(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $media = $this->upload($admin);
        $this->actingAs($admin)->delete(route('admin.media.destroy', $media))->assertSessionHasNoErrors();
        $this->assertSoftDeleted('media', ['id' => $media->id]);
        $this->actingAs($superAdmin)->post(route('admin.media.restore', $media->id))->assertSessionHasNoErrors();
        $this->assertNotSoftDeleted('media', ['id' => $media->id]);
    }

    private function upload(User $user): Media
    {
        $this->actingAs($user)->post(route('admin.media.store'), ['photo' => UploadedFile::fake()->image('media.jpg', 800, 600), 'folder' => 'general'])->assertCreated();

        return Media::firstOrFail();
    }
}
