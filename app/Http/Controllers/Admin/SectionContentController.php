<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageSection;
use App\Services\MediaLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SectionContentController extends Controller
{
    public function edit(PageSection $section): Response
    {
        $section->load('layout', 'draftContent', 'publishedContent');
        $schema = config('section-fields')[$section->section_key]['fields'] ?? [];
        $content = $section->draftContent?->content ?? [];

        return Inertia::render('Admin/SectionEditor', [
            'section' => ['id' => $section->id, 'key' => $section->section_key, 'label' => $section->label, 'component' => $section->component, 'page' => $section->layout->only('page_key', 'label', 'path')],
            'fields' => $schema,
            'sources' => config('section-fields')[$section->section_key]['sources'] ?? [],
            'content' => $content,
            'publishedContent' => $section->publishedContent?->content ?? [],
        ]);
    }

    public function update(Request $request, PageSection $section, MediaLibraryService $mediaLibrary): RedirectResponse
    {
        $schema = config('section-fields')[$section->section_key]['fields'] ?? [];
        $raw = $request->validate(['content' => ['required', 'array']])['content'];
        if (array_diff(array_keys($raw), array_keys($schema))) {
            throw ValidationException::withMessages(['content' => 'Terdapat field yang tidak dikenal.']);
        }
        $content = [];
        foreach ($schema as $key => $field) {
            $value = $raw[$key] ?? null;
            $type = $field['type'] ?? 'text';
            if ($type === 'boolean') {
                $content[$key] = filter_var($value, FILTER_VALIDATE_BOOL);

                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            if ($type === 'gallery') {
                $value = $this->validateGallery($key, $value, $field);
            } elseif ($type === 'number') {
                if (! is_numeric($value)) {
                    throw ValidationException::withMessages(["content.{$key}" => 'Nilai harus berupa angka.']);
                }
                $value = (int) $value;
                if ((isset($field['min']) && $value < $field['min']) || (isset($field['max']) && $value > $field['max'])) {
                    throw ValidationException::withMessages(["content.{$key}" => 'Nilai berada di luar batas.']);
                }
            } elseif ($type === 'select') {
                if (! is_string($value) || ! array_key_exists($value, $field['options'] ?? [])) {
                    throw ValidationException::withMessages(["content.{$key}" => 'Pilihan tidak valid.']);
                }
            } else {
                if (! is_string($value) || mb_strlen($value) > ($field['max'] ?? 2048)) {
                    throw ValidationException::withMessages(["content.{$key}" => 'Isi field terlalu panjang atau tidak valid.']);
                }
                if (in_array($type, ['url', 'video'], true) && ! preg_match('/^(https?:\/\/|\/|#)/', $value)) {
                    throw ValidationException::withMessages(["content.{$key}" => 'Gunakan URL lengkap, path /halaman, atau #anchor.']);
                }
            }
            $content[$key] = $value;
        }
        $this->validateStoryVideo($content);
        $draft = $section->draftContent()->firstOrNew(['status' => 'draft']);
        $draft->fill(['content' => $content, 'version' => ($draft->version ?: 0) + 1, 'created_by' => $request->user()->id])->save();
        $section->layout()->update(['has_unpublished_changes' => true]);
        $section->layout->audits()->create(['user_id' => $request->user()->id, 'action' => 'section_content_saved', 'after_payload' => ['section' => $section->section_key, 'content' => $content]]);
        $mediaFields = collect($schema)->filter(fn ($field) => in_array($field['type'] ?? null, ['image', 'video', 'gallery'], true))->mapWithKeys(fn ($field, $key) => [$key => $content[$key] ?? null])->all();
        $mediaLibrary->syncUsages($section, $mediaFields);

        return back()->with('success', 'Konten section berhasil disimpan sebagai draft.');
    }

    private function validateGallery(string $key, mixed $value, array $field): array
    {
        if (! is_array($value) || count($value) > ($field['max'] ?? 6)) {
            throw ValidationException::withMessages(["content.{$key}" => 'Galeri harus berupa daftar dengan maksimal '.($field['max'] ?? 6).' gambar.']);
        }

        return collect($value)->values()->map(function ($item, $index) use ($key) {
            if (! is_array($item) || blank($item['src'] ?? null) || ! is_string($item['src'])) {
                throw ValidationException::withMessages(["content.{$key}.{$index}.src" => 'Setiap item galeri wajib memiliki gambar.']);
            }

            if (! preg_match('/^(https?:\/\/|\/)/', $item['src'])) {
                throw ValidationException::withMessages(["content.{$key}.{$index}.src" => 'Gunakan gambar dari Media Library atau URL yang valid.']);
            }

            return [
                'src' => mb_substr($item['src'], 0, 500),
                'alt' => mb_substr((string) ($item['alt'] ?? ''), 0, 255),
                'focalPoint' => in_array($item['focalPoint'] ?? 'center', ['left center', 'center', 'right center'], true) ? $item['focalPoint'] : 'center',
            ];
        })->all();
    }

    private function validateStoryVideo(array $content): void
    {
        if (! ($content['storyVideoEnabled'] ?? false)) {
            return;
        }

        foreach (['storyLabel' => 'Teks tombol video wajib diisi.', 'storyTitle' => 'Judul video wajib diisi.', 'storySource' => 'Sumber video wajib dipilih.'] as $key => $message) {
            if (blank($content[$key] ?? null)) {
                throw ValidationException::withMessages(["content.{$key}" => $message]);
            }
        }

        $source = $content['storySource'];
        if (in_array($source, ['youtube', 'external'], true) && blank($content['storyUrl'] ?? null)) {
            throw ValidationException::withMessages(['content.storyUrl' => 'Tautan video wajib diisi untuk sumber ini.']);
        }
        if ($source === 'youtube' && ! preg_match('~^(https?://)?(www\.)?(youtube\.com/(watch\?v=|embed/|shorts/)|youtu\.be/)[\w-]{6,}~i', $content['storyUrl'] ?? '')) {
            throw ValidationException::withMessages(['content.storyUrl' => 'Gunakan tautan video YouTube yang valid.']);
        }
        if ($source === 'media' && blank($content['storyMediaUrl'] ?? null)) {
            throw ValidationException::withMessages(['content.storyMediaUrl' => 'Pilih video dari Media Library.']);
        }
    }
}
