<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\GameMatch;
use App\Models\Media;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_and_publish_general_news(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $category = NewsCategory::firstOrFail();
        $media = $this->media($admin);

        $this->actingAs($admin)->get(route('admin.news.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/NewsEditor')->where('article', null));

        $this->actingAs($admin)->post(route('admin.news.store'), $this->payload($category, $media))
            ->assertSessionHasNoErrors();

        $article = NewsArticle::firstOrFail();
        $this->assertSame('published', $article->status);
        $this->assertDatabaseHas('news_article_audits', ['news_article_id' => $article->id, 'action' => 'created']);
        $this->get(route('news.index'))->assertInertia(fn (Assert $page) => $page->component('Public/News')->has('articles.data', 1));
        $this->get(route('news.show', $article->slug))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Public/NewsDetail')->where('article.title', $article->title));
    }

    public function test_draft_news_is_not_public(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $category = NewsCategory::firstOrFail();
        $media = $this->media($admin);
        $article = NewsArticle::create([...$this->modelPayload($category, $media), 'status' => 'draft', 'published_at' => null]);

        $this->get(route('news.index'))->assertInertia(fn (Assert $page) => $page->has('articles.data', 0));
        $this->get(route('news.show', $article->slug))->assertNotFound();
    }

    public function test_match_report_requires_a_completed_match(): void
    {
        $admin = User::where('role', UserRole::Admin)->firstOrFail();
        $category = NewsCategory::firstOrFail();
        $media = $this->media($admin);
        $scheduled = GameMatch::where('status', 'scheduled')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.news.store'), $this->payload($category, $media, ['type' => 'match_report', 'match_id' => $scheduled->id]))
            ->assertSessionHasErrors('match_id');
    }

    private function payload(NewsCategory $category, Media $media, array $overrides = []): array
    {
        return [...['type' => 'general', 'category_id' => $category->id, 'match_id' => null, 'cover_media_id' => $media->id, 'title' => 'COSMIC Membuka Perjalanan Baru Bersama Komunitas', 'excerpt' => 'Dokumentasi perjalanan terbaru COSMIC bersama komunitas mini soccer Pekanbaru.', 'body' => 'COSMIC kembali membuka perjalanan baru bersama komunitas.\n\nCerita ini menjadi bagian dari dokumentasi resmi klub.', 'seo_title' => '', 'seo_description' => '', 'status' => 'published', 'is_featured' => true, 'published_at' => null, 'gallery_media_ids' => [$media->id]], ...$overrides];
    }

    private function modelPayload(NewsCategory $category, Media $media): array
    {
        return ['uuid' => (string) Str::uuid(), 'slug' => 'berita-draft', 'type' => 'general', 'category_id' => $category->id, 'cover_media_id' => $media->id, 'title' => 'Berita Draft COSMIC', 'excerpt' => 'Berita ini masih berupa draft dan tidak boleh tampil kepada publik.', 'body' => 'Isi berita draft yang cukup panjang untuk kebutuhan pengujian modul berita.', 'created_by' => User::first()->id, 'updated_by' => User::first()->id];
    }

    private function media(User $user): Media
    {
        return Media::create(['uuid' => (string) Str::uuid(), 'disk' => 'public', 'directory' => 'test', 'filename' => 'news.jpg', 'original_name' => 'news.jpg', 'extension' => 'jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 1000, 'checksum' => hash('sha256', 'news'), 'title' => 'Foto Berita', 'folder' => 'general', 'status' => 'active', 'uploaded_by' => $user->id]);
    }
}
