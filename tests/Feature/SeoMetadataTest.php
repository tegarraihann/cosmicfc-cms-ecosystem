<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\ClubProfile;
use App\Models\GameMatch;
use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Services\SeoMetadataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ClubProfile::create([
            'status' => 'published',
            'club_name' => 'COSMIC FC',
            'short_name' => 'COSMIC',
            'short_description' => 'Mini soccer collective dari Pekanbaru.',
            'favicon_url' => '/storage/general/cosmic-icon.png',
            'share_image_url' => '/storage/general/cosmic-share.jpg',
            'seo' => [
                'default_title' => 'COSMIC Mini Soccer Collective',
                'default_description' => 'Website resmi COSMIC Mini Soccer Collective.',
                'keywords' => 'cosmic, mini soccer',
                'indexing_enabled' => true,
                'pages' => [
                    'home' => ['title' => 'Beranda COSMIC', 'description' => 'Berita dan perjalanan terbaru COSMIC.'],
                ],
            ],
        ]);

        Cache::flush();
    }

    public function test_public_page_has_server_rendered_favicon_and_social_metadata(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="icon"', false)
            ->assertSee('/storage/general/cosmic-icon.png', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('Beranda COSMIC', false)
            ->assertSee('name="twitter:card"', false);
    }

    public function test_match_uses_dynamic_social_preview_metadata(): void
    {
        [$home, $away] = $this->clubs();
        $match = GameMatch::create([
            'uuid' => (string) Str::uuid(),
            'slug' => 'cosmic-vs-football-traveller',
            'match_type' => 'friendly',
            'home_club_id' => $home->id,
            'away_club_id' => $away->id,
            'kickoff_at' => '2026-11-07 20:00:00',
            'venue_name' => 'Astaka Arena',
            'status' => 'scheduled',
            'is_public' => true,
        ]);

        $meta = app(SeoMetadataService::class)->forRequest(
            $this->requestFor(route('matches.show', $match->slug)),
        );

        $this->assertSame('COSMIC vs Football Traveller Pekanbaru', Str::before($meta['title'], ' |'));
        $this->assertStringContainsString('Astaka Arena', $meta['description']);
        $this->assertSame('SportsEvent', $meta['schema']['@type']);
        $this->assertSame(route('matches.show', $match->slug), $meta['canonical']);
    }

    public function test_article_uses_editorial_seo_fields_for_social_preview(): void
    {
        $category = NewsCategory::query()->firstOrCreate(
            ['slug' => 'kabar-klub'],
            ['name' => 'Kabar Klub'],
        );
        $article = NewsArticle::create([
            'uuid' => (string) Str::uuid(),
            'slug' => 'cosmic-menang-di-astaka',
            'category_id' => $category->id,
            'title' => 'COSMIC Menang di Astaka',
            'excerpt' => 'COSMIC mencatat hasil positif pada laga terbaru.',
            'body' => 'Laporan lengkap pertandingan COSMIC di Astaka Arena.',
            'seo_title' => 'Kemenangan COSMIC di Astaka Arena',
            'seo_description' => 'Ringkasan pertandingan dan momen penting kemenangan COSMIC.',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        $meta = app(SeoMetadataService::class)->forRequest(
            $this->requestFor(route('news.show', $article->slug)),
        );

        $this->assertStringStartsWith('Kemenangan COSMIC di Astaka Arena', $meta['title']);
        $this->assertSame($article->seo_description, $meta['description']);
        $this->assertSame('NewsArticle', $meta['schema']['@type']);
        $this->assertSame('article', $meta['type']);
    }

    public function test_admin_routes_are_not_indexed_or_managed_by_public_title_component(): void
    {
        $meta = app(SeoMetadataService::class)->forRequest(
            $this->requestFor(route('login')),
        );

        $this->assertSame('noindex, nofollow', $meta['robots']);
        $this->assertFalse($meta['managed']);
    }

    public function test_legacy_cached_profile_without_new_seo_keys_remains_safe(): void
    {
        Cache::forever('club-profile:published', [
            'version' => 1,
            'identity' => [
                'name' => 'COSMIC FC',
                'shortName' => 'COSMIC',
                'description' => 'Website resmi COSMIC.',
            ],
            'branding' => [
                'logo' => null,
                'favicon' => null,
                'shareImage' => null,
            ],
            'seo' => ['keywords' => 'cosmic'],
        ]);

        $meta = app(SeoMetadataService::class)->forRequest(
            $this->requestFor(route('home')),
        );

        $this->assertSame('COSMIC', $meta['title']);
        $this->assertSame('Website resmi COSMIC.', $meta['description']);
        $this->assertSame('cosmic', $meta['keywords']);
    }

    private function requestFor(string $url): Request
    {
        $request = Request::create($url);
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function clubs(): array
    {
        $home = Club::create([
            'uuid' => (string) Str::uuid(),
            'slug' => 'cosmic',
            'official_name' => 'COSMIC FC',
            'display_name' => 'COSMIC',
            'short_name' => 'CSM',
            'type' => 'internal',
            'is_cosmic' => true,
            'status' => 'active',
        ]);
        $away = Club::create([
            'uuid' => (string) Str::uuid(),
            'slug' => 'football-traveller-pekanbaru',
            'official_name' => 'Football Traveller Pekanbaru',
            'display_name' => 'Football Traveller Pekanbaru',
            'short_name' => 'FTP',
            'type' => 'opponent',
            'status' => 'active',
        ]);

        return [$home, $away];
    }
}
