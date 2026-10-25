<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CompetitionMediaPickerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_logo_and_banner_are_stored_and_assigned_to_competition(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.media.store'), [
            'photo' => UploadedFile::fake()->image('competition-logo.png', 600, 600),
            'folder' => 'competitions',
        ])->assertCreated()->assertJsonPath('folder', 'competitions');

        $this->actingAs($admin)->post(route('admin.media.store'), [
            'photo' => UploadedFile::fake()->image('competition-banner.jpg', 1600, 600),
            'folder' => 'competitions',
        ])->assertCreated()->assertJsonPath('folder', 'competitions');

        [$logo, $banner] = Media::orderBy('id')->get();

        $this->actingAs($admin)->post(route('admin.competitions.store'), [
            'name' => 'COSMIC League',
            'short_name' => 'CSL',
            'type' => 'league',
            'organizer' => 'COSMIC',
            'description' => 'Kompetisi komunitas COSMIC.',
            'logo_media_id' => $logo->id,
            'banner_media_id' => $banner->id,
            'website_url' => null,
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        $competition = Competition::firstOrFail();
        $this->assertSame($logo->id, $competition->logo_media_id);
        $this->assertSame($banner->id, $competition->banner_media_id);
        $this->assertDatabaseHas('media_usages', ['media_id' => $logo->id, 'usable_type' => Competition::class, 'usable_id' => $competition->id, 'field_key' => 'logo_media_id']);
        $this->assertDatabaseHas('media_usages', ['media_id' => $banner->id, 'usable_type' => Competition::class, 'usable_id' => $competition->id, 'field_key' => 'banner_media_id']);
    }

    public function test_competition_page_exposes_media_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($admin)->get(route('admin.competitions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Competitions')
                ->where('can.browse_media', true)
                ->where('can.upload_media', true));
    }
}
