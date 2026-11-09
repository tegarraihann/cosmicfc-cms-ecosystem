<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PageLayout;
use App\Models\PageSection;
use App\Models\User;
use App\Services\PageLayoutService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        Cache::flush();
    }

    public function test_public_page_receives_published_sections(): void
    {
        $this->get(route('home'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Home')->has('layoutSections', 6)->where('isPreview', false));
    }

    public function test_admin_can_save_draft_without_changing_public_page(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $layout = PageLayout::with('sections')->where('page_key', 'home')->firstOrFail();
        $payload = $layout->sections->sortBy('sort_order')->values()->map(fn ($section) => [
            'key' => $section->section_key,
            'visible' => $section->section_key !== 'home.match_center',
        ])->all();

        $this->actingAs($admin)->put(route('admin.pages.update', $layout), ['version' => $layout->version, 'sections' => $payload])->assertSessionHasNoErrors();

        $this->assertTrue($layout->fresh()->has_unpublished_changes);
        $this->assertCount(6, app(PageLayoutService::class)->sections('home'));
        $this->assertCount(5, app(PageLayoutService::class)->sections('home', true));
    }

    public function test_publish_applies_draft_and_clears_public_cache(): void
    {
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $layout = PageLayout::with('sections')->where('page_key', 'home')->firstOrFail();
        $layout->sections()->where('section_key', 'home.match_center')->update(['draft_visible' => false]);
        $layout->update(['has_unpublished_changes' => true]);

        app(PageLayoutService::class)->sections('home');
        $this->actingAs($superAdmin)->post(route('admin.pages.publish', $layout))->assertSessionHasNoErrors();

        $this->assertCount(5, app(PageLayoutService::class)->sections('home'));
        $this->assertFalse($layout->fresh()->has_unpublished_changes);
        $this->assertDatabaseHas('page_layout_audits', ['page_layout_id' => $layout->id, 'action' => 'published']);
    }

    public function test_locked_hero_cannot_be_hidden(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $layout = PageLayout::with('sections')->where('page_key', 'home')->firstOrFail();
        $payload = $layout->sections->sortBy('sort_order')->values()->map(fn ($section) => ['key' => $section->section_key, 'visible' => false])->all();

        $this->actingAs($admin)->put(route('admin.pages.update', $layout), ['version' => $layout->version, 'sections' => $payload])->assertSessionHasNoErrors();

        $this->assertTrue($layout->sections()->where('section_key', 'home.hero')->firstOrFail()->draft_visible);
    }

    public function test_admin_can_save_section_content_as_draft(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $section = PageSection::where('section_key', 'home.hero')->firstOrFail();
        $publishedBefore = $section->publishedContent()->firstOrFail()->content;

        $this->actingAs($admin)->get(route('admin.sections.edit', $section))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/SectionEditor')
            ->has('fields.titleTop')
            ->where('fields.storyVideoEnabled.type', 'boolean')
            ->where('fields.storySource.type', 'select')
            ->where('fields.storyMediaUrl.type', 'video'));

        $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'content' => ['titleTop' => 'SATU TIM.', 'titleBottom' => 'SATU CERITA.', 'primaryHref' => '/jadwal'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('SATU TIM.', $section->draftContent()->firstOrFail()->content['titleTop']);
        $this->assertSame($publishedBefore, $section->publishedContent()->firstOrFail()->content);
        $this->assertTrue($section->layout->fresh()->has_unpublished_changes);
    }

    public function test_publishing_page_also_publishes_section_content(): void
    {
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $section = PageSection::where('section_key', 'home.hero')->firstOrFail();
        $section->draftContent()->update(['content' => ['titleTop' => 'COSMIC BARU.']]);
        $layout = $section->layout;
        $layout->update(['has_unpublished_changes' => true]);

        $this->actingAs($superAdmin)->post(route('admin.pages.publish', $layout))->assertSessionHasNoErrors();

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('layoutSections.0.content.titleTop', 'COSMIC BARU.'));
    }

    public function test_section_editor_does_not_expose_technical_json_fields(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $section = PageSection::where('section_key', 'league.matchday')->firstOrFail();

        foreach (config('section-fields') as $definition) {
            foreach ($definition['fields'] ?? [] as $field) {
                $this->assertNotSame('json', $field['type'] ?? null);
            }
        }

        $this->actingAs($admin)->get(route('admin.sections.edit', $section))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/SectionEditor')
            ->has('fields', 0)
            ->has('sources', 1)
            ->where('sources.0.route', 'admin.matches.index'));
    }

    public function test_next_match_editors_expose_editorial_fields_and_automatic_match_source(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        foreach (['home.next_match', 'schedule.next_match'] as $sectionKey) {
            $section = PageSection::where('section_key', $sectionKey)->firstOrFail();

            $this->actingAs($admin)->get(route('admin.sections.edit', $section))
                ->assertOk()->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/SectionEditor')
                    ->has('fields', 4)
                    ->where('fields.label.default', 'Next Match')
                    ->where('fields.emptyTitle.default', 'Pertandingan berikutnya belum dijadwalkan.')
                    ->where('fields.emptyDescription.type', 'textarea')
                    ->where('fields.backgroundImage.type', 'image')
                    ->has('sources', 1)
                    ->where('sources.0.route', 'admin.matches.index'));
        }
    }

    public function test_dynamic_module_data_cannot_be_saved_as_section_content(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $section = PageSection::where('section_key', 'team.directory')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'content' => ['players' => [['name' => 'Data manual']]],
        ])->assertSessionHasErrors('content');
    }

    public function test_cleanup_migration_removes_legacy_dynamic_data_but_keeps_editorial_content(): void
    {
        $section = PageSection::where('section_key', 'home.match_center')->firstOrFail();
        $section->draftContent()->update(['content' => [
            'title' => 'Match Center COSMIC',
            'upcoming' => [['opponent' => 'Data lama']],
            'results' => [['score' => '1-0']],
        ]]);

        $migration = require database_path('migrations/2026_11_03_000000_remove_technical_json_from_section_content.php');
        $migration->up();

        $content = $section->draftContent()->firstOrFail()->content;
        $this->assertSame('Match Center COSMIC', $content['title']);
        $this->assertArrayNotHasKey('upcoming', $content);
        $this->assertArrayNotHasKey('results', $content);
    }

    public function test_admin_can_manage_club_culture_gallery_without_json_text(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $section = PageSection::where('section_key', 'home.club_culture')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'content' => [
                'gallery' => [
                    ['src' => '/storage/uploads/general/culture.webp', 'alt' => 'Kebersamaan pemain COSMIC', 'focalPoint' => 'center'],
                ],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame(
            'Kebersamaan pemain COSMIC',
            $section->draftContent()->firstOrFail()->content['gallery'][0]['alt'],
        );
    }

    public function test_admin_can_configure_our_story_youtube_video(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $section = PageSection::where('section_key', 'home.hero')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'content' => [
                'storyVideoEnabled' => true,
                'storyLabel' => 'Play Video Our Story',
                'storyTitle' => 'Perjalanan COSMIC',
                'storySource' => 'youtube',
                'storyUrl' => 'https://www.youtube.com/watch?v=abcdefghijk',
            ],
        ])->assertSessionHasNoErrors();

        $content = $section->draftContent()->firstOrFail()->content;
        $this->assertTrue($content['storyVideoEnabled']);
        $this->assertSame('youtube', $content['storySource']);
        $this->assertSame('https://www.youtube.com/watch?v=abcdefghijk', $content['storyUrl']);
    }

    public function test_enabled_our_story_video_requires_a_valid_source_url(): void
    {
        $admin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $section = PageSection::where('section_key', 'home.hero')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.sections.update', $section), [
            'content' => [
                'storyVideoEnabled' => true,
                'storyLabel' => 'Play Video Our Story',
                'storyTitle' => 'Perjalanan COSMIC',
                'storySource' => 'youtube',
                'storyUrl' => 'https://example.com/video',
            ],
        ])->assertSessionHasErrors('content.storyUrl');
    }
}
