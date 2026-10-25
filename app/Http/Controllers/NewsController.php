<?php

namespace App\Http\Controllers;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $base = NewsArticle::with(['category', 'cover', 'author'])->whereIn('status', ['published', 'scheduled'])->where('published_at', '<=', now());
        $featured = (clone $base)->where('is_featured', true)->latest('published_at')->first();
        $articles = $base->when($request->string('search')->toString(), fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('excerpt', 'like', "%{$v}%")))->when($request->integer('category'), fn ($q, $v) => $q->where('category_id', $v))->latest('published_at')->paginate(9)->withQueryString()->through(fn ($a) => $this->resource($a));

        return Inertia::render('Public/News', ['articles' => $articles, 'featured' => $featured ? $this->resource($featured) : null, 'categories' => NewsCategory::where('is_active', true)->orderBy('display_order')->get(['id', 'name']), 'filters' => $request->only(['search', 'category'])]);
    }

    public function show(string $slug): Response
    {
        $article = NewsArticle::with(['category', 'cover', 'author', 'gallery', 'match.homeClub', 'match.awayClub', 'match.events.primaryPlayer', 'match.events.secondaryPlayer'])->where('slug', $slug)->whereIn('status', ['published', 'scheduled'])->where('published_at', '<=', now())->firstOrFail();
        $related = NewsArticle::with(['category', 'cover'])->whereIn('status', ['published', 'scheduled'])->where('published_at', '<=', now())->whereKeyNot($article->id)->where('category_id', $article->category_id)->latest('published_at')->limit(3)->get()->map(fn ($a) => $this->resource($a));

        return Inertia::render('Public/NewsDetail', ['article' => [...$this->resource($article), 'body' => $article->body, 'seo_title' => $article->seo_title, 'seo_description' => $article->seo_description, 'gallery' => $article->gallery->map(fn ($m) => ['id' => $m->id, 'url' => $m->url, 'alt' => $m->alt_text ?: $m->title, 'caption' => $m->pivot->caption]), 'match' => $article->match ? ['home' => $article->match->homeClub?->display_name, 'away' => $article->match->awayClub?->display_name, 'home_score' => $article->match->home_score, 'away_score' => $article->match->away_score, 'date' => $article->match->kickoff_at?->locale('id')->translatedFormat('d F Y'), 'venue' => $article->match->venue_name, 'slug' => $article->match->slug, 'events' => $article->match->events->where('is_public', true)->values()->map(fn ($e) => ['minute' => $e->minute, 'type' => $e->type, 'player' => $e->primaryPlayer?->display_name ?: $e->primary_player_name, 'assist' => $e->secondaryPlayer?->display_name ?: $e->secondary_player_name])] : null], 'related' => $related]);
    }

    private function resource(NewsArticle $a): array
    {
        return ['id' => $a->id, 'slug' => $a->slug, 'title' => $a->title, 'excerpt' => $a->excerpt, 'type' => $a->type, 'category' => $a->category?->name, 'cover' => $a->cover?->url, 'author' => $a->author?->name ?: 'COSMIC', 'published_at' => $a->published_at?->locale('id')->translatedFormat('d F Y'), 'href' => route('news.show',$a->slug)];
    }
}
