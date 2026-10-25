<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageLayout;
use App\Services\PageLayoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PageManagementController extends Controller
{
    public function index(?string $page = null): Response
    {
        $layouts = PageLayout::with(['sections' => fn ($query) => $query->orderBy('draft_sort_order'), 'publisher:id,name'])
            ->orderBy('id')->get();
        $selected = $layouts->firstWhere('page_key', $page) ?? $layouts->first();

        return Inertia::render('Admin/PageManagement', [
            'pages' => $layouts->map(fn ($layout) => [
                'key' => $layout->page_key, 'label' => $layout->label, 'path' => $layout->path,
                'dirty' => $layout->has_unpublished_changes, 'version' => $layout->version,
            ]),
            'selectedPage' => $selected ? [
                'id' => $selected->id, 'key' => $selected->page_key, 'label' => $selected->label, 'path' => $selected->path,
                'version' => $selected->version, 'dirty' => $selected->has_unpublished_changes,
                'published_at' => $selected->published_at?->translatedFormat('d M Y, H:i'),
                'publisher' => $selected->publisher?->name,
                'sections' => $selected->sections->map(fn ($section) => [
                    'id' => $section->id, 'key' => $section->section_key, 'label' => $section->label, 'component' => $section->component,
                    'locked' => $section->is_locked, 'visible' => $section->draft_visible,
                    'published_visible' => $section->is_visible,
                ])->values(),
            ] : null,
            'canUpdate' => request()->user()->hasPermission('landing.update'),
            'canPreview' => request()->user()->hasPermission('landing.preview'),
            'canPublish' => request()->user()->hasPermission('landing.publish'),
        ]);
    }

    public function update(Request $request, PageLayout $layout): RedirectResponse
    {
        $data = $request->validate(['version' => ['required', 'integer'], 'sections' => ['required', 'array'], 'sections.*.key' => ['required', 'string'], 'sections.*.visible' => ['required', 'boolean']]);
        if ((int) $data['version'] !== $layout->version) {
            throw ValidationException::withMessages(['version' => 'Konfigurasi telah berubah. Muat ulang halaman.']);
        }
        $stored = $layout->sections()->get()->keyBy('section_key');
        if (collect($data['sections'])->pluck('key')->sort()->values()->all() !== $stored->keys()->sort()->values()->all()) {
            throw ValidationException::withMessages(['sections' => 'Daftar section tidak valid.']);
        }

        DB::transaction(function () use ($layout, $data, $stored, $request) {
            $before = $this->snapshot($layout, true);
            foreach ($data['sections'] as $index => $item) {
                $section = $stored[$item['key']];
                $section->update(['draft_visible' => $section->is_locked ? true : $item['visible'], 'draft_sort_order' => ($index + 1) * 10]);
            }
            $layout->update(['has_unpublished_changes' => true]);
            $layout->audits()->create(['user_id' => $request->user()->id, 'action' => 'draft_saved', 'before_payload' => $before, 'after_payload' => $this->snapshot($layout->fresh(), true)]);
        });

        return back()->with('success', 'Draft halaman berhasil disimpan.');
    }

    public function publish(Request $request, PageLayout $layout, PageLayoutService $service): RedirectResponse
    {
        DB::transaction(function () use ($layout, $request) {
            $before = $this->snapshot($layout, false);
            foreach ($layout->sections as $section) {
                $section->update(['is_visible' => $section->draft_visible, 'sort_order' => $section->draft_sort_order]);
                $draft = $section->draftContent()->first();
                if ($draft) {
                    $published = $section->publishedContent()->firstOrNew(['status' => 'published']);
                    $published->fill(['content' => $draft->content, 'version' => ($published->version ?: 0) + 1, 'created_by' => $draft->created_by, 'published_by' => $request->user()->id, 'published_at' => now()])->save();
                }
            }
            $layout->update(['version' => $layout->version + 1, 'has_unpublished_changes' => false, 'published_at' => now(), 'published_by' => $request->user()->id]);
            $layout->audits()->create(['user_id' => $request->user()->id, 'action' => 'published', 'before_payload' => $before, 'after_payload' => $this->snapshot($layout->fresh(), false)]);
        });
        $service->forget($layout->page_key);

        return back()->with('success', 'Perubahan telah dipublikasikan.');
    }

    public function discard(Request $request, PageLayout $layout): RedirectResponse
    {
        DB::transaction(function () use ($layout, $request) {
            foreach ($layout->sections as $section) {
                $section->update(['draft_visible' => $section->is_visible, 'draft_sort_order' => $section->sort_order]);
                $published = $section->publishedContent()->first();
                $draft = $section->draftContent()->firstOrNew(['status' => 'draft']);
                $draft->fill(['content' => $published?->content ?? [], 'version' => ($draft->version ?: 0) + 1, 'created_by' => $request->user()->id])->save();
            }
            $layout->update(['has_unpublished_changes' => false]);
            $layout->audits()->create(['user_id' => $request->user()->id, 'action' => 'draft_discarded']);
        });

        return back()->with('success', 'Draft dikembalikan ke versi publik.');
    }

    public function preview(PageLayout $layout): RedirectResponse
    {
        return redirect()->away(URL::temporarySignedRoute('preview.page', now()->addMinutes(30), ['page' => $layout->page_key]));
    }

    private function snapshot(PageLayout $layout, bool $draft): array
    {
        return $layout->sections()->orderBy($draft ? 'draft_sort_order' : 'sort_order')->get()->map(fn ($section) => ['key' => $section->section_key, 'visible' => $draft ? $section->draft_visible : $section->is_visible])->all();
    }
}
