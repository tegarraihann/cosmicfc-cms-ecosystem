<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameMatch;
use App\Models\Media;
use App\Models\NewsArticle;
use App\Models\NewsArticleAudit;
use App\Models\NewsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NewsController extends Controller
{
    public function index(Request $request): Response
    {
        $articles = NewsArticle::with(['category', 'cover', 'author', 'gallery'])->when($request->string('search')->toString(), fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('excerpt', 'like', "%{$v}%")))->when($request->string('status')->toString(), fn ($q, $v) => $q->where('status', $v))->when($request->string('type')->toString(), fn ($q, $v) => $q->where('type', $v))->when($request->integer('category_id'), fn ($q, $v) => $q->where('category_id', $v))->latest()->paginate(12)->withQueryString();

        return Inertia::render('Admin/News', [
            'articles' => $articles->through(fn ($a) => [...$a->toArray(), 'category_name' => $a->category?->name, 'cover_url' => $a->cover?->url, 'author_name' => $a->author?->name, 'published_label' => $a->published_at?->locale('id')->translatedFormat('d M Y, H.i')]),
            'categories' => NewsCategory::withCount('articles')->orderBy('display_order')->get(),
            'matches' => GameMatch::with(['homeClub', 'awayClub'])->whereIn('status', ['completed', 'walkover'])->latest('kickoff_at')->get()->map(fn ($m) => ['id' => $m->id, 'label' => "{$m->homeClub?->display_name} {$m->home_score}–{$m->away_score} {$m->awayClub?->display_name}"]),
            'media' => Media::where('status', 'active')->latest()->limit(150)->get()->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'url' => $m->url]),
            'filters' => $request->only(['search', 'status', 'type', 'category_id']),
            'stats' => ['total' => NewsArticle::count(), 'draft' => NewsArticle::where('status', 'draft')->count(), 'scheduled' => NewsArticle::where('status', 'scheduled')->count(), 'published' => NewsArticle::where('status', 'published')->count()],
            'can' => ['create' => $request->user()->hasPermission('news.create'), 'update' => $request->user()->hasPermission('news.update'), 'publish' => $request->user()->hasPermission('news.publish'), 'archive' => $request->user()->hasPermission('news.archive'), 'categories' => $request->user()->hasPermission('news.categories')],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/NewsEditor', [...$this->editorData($request), 'article' => null]);
    }

    public function edit(Request $request, NewsArticle $article): Response
    {
        $article->load('gallery');

        return Inertia::render('Admin/NewsEditor', [
            ...$this->editorData($request),
            'article' => [...$article->toArray(), 'gallery_media_ids' => $article->gallery->pluck('id')->values(), 'published_at' => $article->published_at?->format('Y-m-d\TH:i')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($request, $data) {
            $article = NewsArticle::create([...$data, 'uuid' => (string) Str::uuid(), 'slug' => $this->uniqueSlug($data['title']), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->syncGallery($article, $request->input('gallery_media_ids', []));
            NewsArticleAudit::create(['news_article_id' => $article->id, 'user_id' => $request->user()->id, 'action' => 'created', 'after_payload' => $article->toArray()]);
        });

        return to_route('admin.news.index')->with('success', 'Berita berhasil dibuat.');
    }

    public function update(Request $request, NewsArticle $article): RedirectResponse
    {
        $before = $article->toArray();
        $data = $this->validated($request, $article);
        DB::transaction(function () use ($request, $article, $data, $before) {
            $article->update([...$data, 'slug' => $this->uniqueSlug($data['title'], $article), 'updated_by' => $request->user()->id]);
            $this->syncGallery($article, $request->input('gallery_media_ids', []));
            NewsArticleAudit::create(['news_article_id' => $article->id, 'user_id' => $request->user()->id, 'action' => $article->status === 'published' ? 'published' : 'updated', 'before_payload' => $before, 'after_payload' => $article->fresh()->toArray()]);
        });

        return to_route('admin.news.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function archive(Request $request, NewsArticle $article): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('news.archive'), 403);
        $before = $article->toArray();
        $article->update(['status' => 'archived', 'is_featured' => false, 'updated_by' => $request->user()->id]);
        NewsArticleAudit::create(['news_article_id' => $article->id, 'user_id' => $request->user()->id, 'action' => 'archived', 'before_payload' => $before, 'after_payload' => $article->fresh()->toArray()]);

        return back()->with('success', 'Berita berhasil diarsipkan.');
    }

    public function restore(Request $request, NewsArticle $article): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('news.archive'), 403);
        $article->update(['status' => 'draft', 'updated_by' => $request->user()->id]);
        NewsArticleAudit::create(['news_article_id' => $article->id, 'user_id' => $request->user()->id, 'action' => 'restored', 'after_payload' => $article->fresh()->toArray()]);

        return back()->with('success', 'Berita dipulihkan sebagai draft.');
    }

    public function saveCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['id' => ['nullable', 'exists:news_categories,id'], 'name' => ['required', 'string', 'max:80'], 'is_active' => ['boolean']]);
        NewsCategory::updateOrCreate(['id' => $data['id'] ?? null], ['name' => $data['name'], 'slug' => Str::slug($data['name']), 'is_active' => $data['is_active'], 'display_order' => ((int) NewsCategory::max('display_order')) + 10]);

        return back()->with('success', 'Kategori berita berhasil disimpan.');
    }

    private function validated(Request $request, ?NewsArticle $article = null): array
    {
        $data = $request->validate(['type' => ['required', Rule::in(['general', 'match_report'])], 'category_id' => ['required', 'exists:news_categories,id'], 'match_id' => [Rule::requiredIf(fn () => $request->input('type') === 'match_report'), 'nullable', 'exists:matches,id'], 'cover_media_id' => ['required', 'exists:media,id'], 'title' => ['required', 'string', 'min:10', 'max:150'], 'excerpt' => ['required', 'string', 'max:220'], 'body' => ['required', 'string', 'min:30'], 'seo_title' => ['nullable', 'string', 'max:160'], 'seo_description' => ['nullable', 'string', 'max:220'], 'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'archived'])], 'is_featured' => ['boolean'], 'published_at' => [Rule::requiredIf(fn () => $request->input('status') === 'scheduled'), 'nullable', 'date'], 'gallery_media_ids' => ['array'], 'gallery_media_ids.*' => ['exists:media,id']]);
        if (in_array($data['status'], ['published', 'scheduled'])) {
            abort_unless($request->user()->hasPermission('news.publish'), 403);
        }
        if ($data['type'] === 'match_report' && ! GameMatch::whereKey($data['match_id'])->whereIn('status', ['completed', 'walkover'])->exists()) {
            return throw ValidationException::withMessages(['match_id' => 'Laporan pertandingan hanya dapat memakai pertandingan yang sudah selesai.']);
        }
        if ($data['status'] === 'published') {
            $data['published_at'] = $data['published_at'] ?? now();
        }
        if ($data['status'] === 'draft' || $data['status'] === 'archived') {
            $data['published_at'] = null;
        }
        if ($data['is_featured']) {
            NewsArticle::whereKeyNot($article?->id)->update(['is_featured' => false]);
        }

        return collect($data)->except('gallery_media_ids')->all();
    }

    private function uniqueSlug(string $title, ?NewsArticle $article = null): string
    {
        $base = Str::slug($title) ?: 'berita';
        $slug = $base;
        $i = 2;
        while (NewsArticle::where('slug', $slug)->when($article, fn ($q) => $q->whereKeyNot($article->id))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function syncGallery(NewsArticle $article, array $ids): void
    {
        $article->gallery()->sync(collect($ids)->unique()->values()->mapWithKeys(fn ($id, $i) => [$id => ['display_order' => $i + 1]])->all());
    }

    private function editorData(Request $request): array
    {
        return [
            'categories' => NewsCategory::where('is_active', true)->orderBy('display_order')->get(['id', 'name']),
            'matches' => GameMatch::with(['homeClub', 'awayClub'])->whereIn('status', ['completed', 'walkover'])->latest('kickoff_at')->get()->map(fn ($m) => ['id' => $m->id, 'label' => "{$m->homeClub?->display_name} {$m->home_score}–{$m->away_score} {$m->awayClub?->display_name}"]),
            'media' => Media::where('status', 'active')->latest()->limit(150)->get()->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'url' => $m->url]),
            'canPublish' => $request->user()->hasPermission('news.publish'),
        ];
    }
}
