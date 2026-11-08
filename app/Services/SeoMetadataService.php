<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoMetadataService
{
    private const PUBLIC_ROUTES = [
        'home', 'schedule', 'league', 'team', 'news.index', 'news.show', 'matches.show',
    ];

    public function __construct(private readonly ClubProfileService $profiles) {}

    public function forRequest(Request $request): array
    {
        $site = $this->profiles->published();
        $seo = is_array($site['seo'] ?? null) ? $site['seo'] : [];
        $siteName = $site['identity']['shortName'] ?? $site['identity']['name'] ?? 'COSMIC';
        $routeName = $request->route()?->getName();
        $managed = in_array($routeName, self::PUBLIC_ROUTES, true);
        $indexable = $managed
            && (bool) ($seo['indexing_enabled'] ?? true);

        $meta = [
            'title' => ($seo['default_title'] ?? '') ?: $siteName,
            'description' => ($seo['default_description'] ?? '') ?: ($site['identity']['description'] ?? 'Website resmi COSMIC Mini Soccer Collective.'),
            'keywords' => $seo['keywords'] ?? '',
            'canonical' => $request->url(),
            'image' => $this->absolute($site['branding']['shareImage'] ?? $site['branding']['logo'] ?? '/Assets/Logo/Logo.jpeg'),
            'favicon' => $this->versionedAsset(
                $site['branding']['favicon'] ?? '/favicon.ico',
                (int) ($site['version'] ?? 1),
            ),
            'type' => 'website',
            'siteName' => $siteName,
            'robots' => $indexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow',
            'twitterCard' => 'summary_large_image',
            'schema' => null,
            'managed' => $managed,
        ];

        if (! $indexable) {
            return $meta;
        }

        if (in_array($routeName, ['home', 'schedule', 'league', 'team', 'news.index'], true)) {
            return $this->forPublicPage($meta, $seo, $routeName, $site);
        }

        if ($routeName === 'matches.show') {
            return $this->forMatch($meta, (string) $request->route('match'));
        }

        if ($routeName === 'news.show') {
            return $this->forArticle($meta, (string) $request->route('slug'));
        }

        return $meta;
    }

    private function forPublicPage(array $meta, array $seo, string $routeName, array $site): array
    {
        $key = ['news.index' => 'news'][$routeName] ?? $routeName;
        $defaults = [
            'home' => ['title' => $meta['title'], 'description' => $meta['description']],
            'schedule' => ['title' => 'Jadwal Pertandingan', 'description' => 'Jadwal pertandingan mendatang, pertandingan live, dan hasil terbaru COSMIC.'],
            'league' => ['title' => 'Liga & Klasemen', 'description' => 'Klasemen, statistik, performa, dan perjalanan COSMIC dalam kompetisi.'],
            'team' => ['title' => 'Tim COSMIC', 'description' => 'Kenali pemain, kapten, dan staf yang menjadi bagian dari COSMIC.'],
            'news' => ['title' => 'News', 'description' => 'Berita, laporan pertandingan, dan cerita terbaru COSMIC.'],
        ];
        $page = data_get($seo, "pages.{$key}", []);
        $title = $page['title'] ?? '';

        $title = trim($title) !== '' ? $title : $defaults[$key]['title'];
        $description = $page['description'] ?? '';
        $description = trim($description) !== '' ? $description : $defaults[$key]['description'];
        $meta['title'] = $key === 'home' ? $title : $this->withSiteName($title, $meta['siteName']);
        $meta['description'] = $description;

        if ($key === 'home') {
            $meta['schema'] = [
                '@context' => 'https://schema.org',
                '@type' => 'SportsOrganization',
                'name' => $site['identity']['name'] ?? $meta['siteName'],
                'url' => route('home'),
                'logo' => $this->absolute($site['branding']['logo'] ?? null),
                'description' => $meta['description'],
            ];
        }

        return $meta;
    }

    private function forMatch(array $meta, string $slug): array
    {
        $match = GameMatch::query()
            ->with(['homeClub', 'awayClub', 'cover', 'season.competition'])
            ->where('slug', $slug)
            ->where('is_public', true)
            ->first();

        if (! $match) {
            return $meta;
        }

        $home = $match->homeClub?->display_name ?? 'Tuan Rumah';
        $away = $match->awayClub?->display_name ?? 'Lawan';
        $type = [
            'competitive' => 'Pertandingan kompetisi', 'friendly' => 'Friendly match',
            'sparing' => 'Sparing', 'tournament' => 'Turnamen', 'exhibition' => 'Ekshibisi',
        ][$match->match_type] ?? 'Pertandingan';
        $date = $match->kickoff_at?->locale('id')->translatedFormat('d F Y');
        $time = $match->kickoff_at?->format('H.i').' WIB';
        $venue = $match->venue_name ? " di {$match->venue_name}" : '';
        $score = $match->home_score !== null && $match->away_score !== null
            ? " Hasil akhir {$match->home_score}-{$match->away_score}."
            : '';

        $meta['title'] = $this->withSiteName("{$home} vs {$away}", $meta['siteName']);
        $meta['description'] = trim("{$type} {$home} melawan {$away}".($date ? " pada {$date}" : '').($time ? " pukul {$time}" : '')."{$venue}.{$score}");
        $meta['canonical'] = route('matches.show', $match->slug);
        $meta['image'] = $this->absolute($match->cover?->url) ?: $meta['image'];
        $meta['type'] = 'article';
        $meta['schema'] = [
            '@context' => 'https://schema.org',
            '@type' => 'SportsEvent',
            'name' => "{$home} vs {$away}",
            'url' => $meta['canonical'],
            'image' => array_values(array_filter([$meta['image']])),
            'startDate' => $match->kickoff_at?->toIso8601String(),
            'eventStatus' => $this->eventStatus($match->status),
            'location' => $match->venue_name ? [
                '@type' => 'Place',
                'name' => $match->venue_name,
                'address' => $match->venue_address,
            ] : null,
            'homeTeam' => ['@type' => 'SportsTeam', 'name' => $home],
            'awayTeam' => ['@type' => 'SportsTeam', 'name' => $away],
        ];

        return $meta;
    }

    private function forArticle(array $meta, string $slug): array
    {
        $article = NewsArticle::query()
            ->with(['cover', 'author'])
            ->where('slug', $slug)
            ->whereIn('status', ['published', 'scheduled'])
            ->where('published_at', '<=', now())
            ->first();

        if (! $article) {
            return $meta;
        }

        $meta['title'] = $this->withSiteName($article->seo_title ?: $article->title, $meta['siteName']);
        $meta['description'] = $article->seo_description ?: $article->excerpt;
        $meta['canonical'] = route('news.show', $article->slug);
        $meta['image'] = $this->absolute($article->cover?->url) ?: $meta['image'];
        $meta['type'] = 'article';
        $meta['publishedTime'] = $article->published_at?->toIso8601String();
        $meta['schema'] = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $article->title,
            'description' => $meta['description'],
            'url' => $meta['canonical'],
            'mainEntityOfPage' => $meta['canonical'],
            'image' => array_values(array_filter([$meta['image']])),
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'author' => ['@type' => 'Person', 'name' => $article->author?->name ?: $meta['siteName']],
            'publisher' => ['@type' => 'Organization', 'name' => $meta['siteName']],
        ];

        return $meta;
    }

    private function absolute(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://']) ? $url : url('/'.ltrim($url, '/'));
    }

    private function withSiteName(string $title, string $siteName): string
    {
        return Str::contains(Str::lower($title), Str::lower($siteName)) ? $title : "{$title} | {$siteName}";
    }

    private function versionedAsset(string $url, int $version): string
    {
        $absolute = $this->absolute($url);

        return $absolute.(Str::contains($absolute, '?') ? '&' : '?').'v='.$version;
    }

    private function eventStatus(string $status): string
    {
        return match ($status) {
            'completed', 'walkover' => 'https://schema.org/EventCompleted',
            'cancelled' => 'https://schema.org/EventCancelled',
            'postponed' => 'https://schema.org/EventPostponed',
            default => 'https://schema.org/EventScheduled',
        };
    }
}
