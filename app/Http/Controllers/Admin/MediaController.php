<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Media;
use App\Models\MediaAudit;
use App\Models\MediaTag;
use App\Services\MediaLibraryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'folder' => ['nullable', 'in:general,players,staff,clubs,competitions,matches,sponsors'], 'status' => ['nullable', 'in:active,archived,trashed'], 'sort' => ['nullable', 'in:newest,oldest,name,size']]);
        $query = Media::query()->with(['uploader:id,name', 'tags:id,name'])->withCount('usages');
        if (($filters['status'] ?? null) === 'trashed') {
            $query->onlyTrashed();
        } elseif (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', 'active');
        }
        if ($search = $filters['search'] ?? null) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('original_name', 'like', "%{$search}%")->orWhere('alt_text', 'like', "%{$search}%"));
        }
        if ($folder = $filters['folder'] ?? null) {
            $query->where('folder', $folder);
        }
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(), 'name' => $query->orderBy('title'), 'size' => $query->orderByDesc('size_bytes'), default => $query->latest()
        };

        return Inertia::render('Admin/MediaLibrary', [
            'media' => $query->paginate(24)->withQueryString()->through(fn (Media $media) => $this->resource($media)),
            'filters' => $filters,
            'stats' => ['total' => Media::count(), 'size' => Media::sum('size_bytes'), 'unused' => Media::doesntHave('usages')->count(), 'trashed' => Media::onlyTrashed()->count()],
            'can' => ['upload' => $request->user()->hasPermission('media.upload'), 'update' => $request->user()->hasPermission('media.update'), 'delete' => $request->user()->hasPermission('media.delete'), 'restore' => $request->user()->hasPermission('media.restore')],
        ]);
    }

    public function items(): JsonResponse
    {
        return response()->json(['data' => Media::where('status', 'active')->latest()->limit(100)->get()->map(fn (Media $media) => $this->resource($media))]);
    }

    public function store(StoreMediaRequest $request, MediaLibraryService $service): JsonResponse
    {
        $result = $service->store($request->file('photo'), $request->validated('folder', 'general'), $request->user());

        return response()->json([...$this->resource($result['media']), 'duplicate' => $result['duplicate']], $result['duplicate'] ? 200 : 201);
    }

    public function update(Request $request, Media $media): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'alt_text' => ['nullable', 'string', 'max:255'], 'caption' => ['nullable', 'string', 'max:1000'], 'folder' => ['required', 'in:general,players,staff,clubs,competitions,matches,sponsors'], 'status' => ['required', 'in:active,archived'], 'tags' => ['array', 'max:10'], 'tags.*' => ['string', 'max:40']]);
        $before = $media->only(['title', 'alt_text', 'caption', 'folder', 'status']);
        $media->update(collect($data)->except('tags')->all());
        $tagIds = collect($data['tags'] ?? [])->filter()->unique()->map(function ($name) {
            $tag = MediaTag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);

            return $tag->id;
        });
        $media->tags()->sync($tagIds);
        MediaAudit::create(['media_id' => $media->id, 'user_id' => $request->user()->id, 'action' => 'metadata_updated', 'before_payload' => $before, 'after_payload' => $media->fresh()->only(['title', 'alt_text', 'caption', 'folder', 'status'])]);

        return back()->with('success', 'Metadata media berhasil diperbarui.');
    }

    public function destroy(Request $request, Media $media): RedirectResponse
    {
        if ($media->usages()->exists()) {
            return back()->withErrors(['media' => 'Media masih digunakan dan tidak dapat dihapus.']);
        }
        DB::transaction(function () use ($request, $media) {
            $media->update(['deleted_by' => $request->user()->id]);
            $media->delete();
            MediaAudit::create(['media_id' => $media->id, 'user_id' => $request->user()->id, 'action' => 'deleted']);
        });

        return back()->with('success', 'Media dipindahkan ke sampah.');
    }

    public function restore(Request $request, int $media): RedirectResponse
    {
        $item = Media::onlyTrashed()->findOrFail($media);
        $item->restore();
        $item->update(['deleted_by' => null]);
        MediaAudit::create(['media_id' => $item->id, 'user_id' => $request->user()->id, 'action' => 'restored']);

        return back()->with('success', 'Media berhasil dipulihkan.');
    }

    private function resource(Media $media): array
    {
        return ['id' => $media->id, 'uuid' => $media->uuid, 'title' => $media->title, 'name' => $media->original_name, 'url' => $media->url, 'alt_text' => $media->alt_text, 'caption' => $media->caption, 'folder' => $media->folder, 'status' => $media->status, 'mime_type' => $media->mime_type, 'size_bytes' => $media->size_bytes, 'width' => $media->width, 'height' => $media->height, 'usage_count' => $media->usages_count ?? $media->usages()->count(), 'uploader' => $media->uploader?->name, 'tags' => $media->tags?->pluck('name')->values() ?? [], 'created_at' => $media->created_at?->translatedFormat('d M Y, H:i'), 'deleted_at' => $media->deleted_at?->toISOString()];
    }
}
