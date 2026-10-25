<?php

namespace App\Services;

use App\Models\PageLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PageLayoutService
{
    public function sections(string $pageKey, bool $draft = false): Collection
    {
        if (! Schema::hasTable('page_layouts')) {
            return $this->fallback($pageKey);
        }

        $resolver = function () use ($pageKey, $draft) {
            $layout = PageLayout::where('page_key', $pageKey)->first();
            if (! $layout) {
                return $this->fallback($pageKey);
            }

            return $layout->sections()->with($draft ? 'draftContent' : 'publishedContent')
                ->where($draft ? 'draft_visible' : 'is_visible', true)
                ->orderBy($draft ? 'draft_sort_order' : 'sort_order')
                ->get()
                ->map(fn ($section) => ['key' => $section->section_key, 'component' => $section->component, 'content' => ($draft ? $section->draftContent?->content : $section->publishedContent?->content) ?? []]);
        };

        return $draft ? $resolver() : Cache::rememberForever("page-layout:{$pageKey}:published", $resolver);
    }

    public function forget(string $pageKey): void
    {
        Cache::forget("page-layout:{$pageKey}:published");
    }

    private function fallback(string $pageKey): Collection
    {
        return collect(config("page-sections.{$pageKey}.sections", []))->map(fn ($section) => [
            'key' => $section['key'], 'component' => $section['component'], 'content' => [],
        ]);
    }
}
