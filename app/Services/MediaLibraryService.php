<?php

namespace App\Services;

use App\Models\Media;
use App\Models\MediaAudit;
use App\Models\MediaUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class MediaLibraryService
{
    public function store(UploadedFile $file, string $folder, User $user): array
    {
        $checksum = hash_file('sha256', $file->getRealPath());
        if ($duplicate = Media::where('checksum', $checksum)->first()) {
            return ['media' => $duplicate, 'duplicate' => true];
        }
        $uuid = (string) Str::uuid();
        $extension = strtolower($file->guessExtension() ?: $file->extension());
        $filename = "{$uuid}.{$extension}";
        $directory = "uploads/{$folder}";
        $path = $file->storeAs($directory, $filename, 'public');
        try {
            $dimensions = @getimagesize($file->getRealPath()) ?: [null, null];
            $media = Media::create(['uuid' => $uuid, 'disk' => 'public', 'directory' => $directory, 'filename' => $filename, 'original_name' => $file->getClientOriginalName(), 'extension' => $extension, 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'width' => $dimensions[0], 'height' => $dimensions[1], 'checksum' => $checksum, 'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 'folder' => $folder, 'uploaded_by' => $user->id]);
            MediaAudit::create(['media_id' => $media->id, 'user_id' => $user->id, 'action' => 'uploaded', 'after_payload' => $media->only(['original_name', 'folder', 'mime_type', 'size_bytes'])]);

            return ['media' => $media, 'duplicate' => false];
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function import(string $path, ?User $user = null): ?Media
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path) || ! preg_match('/\.(jpe?g|png|webp)$/i', $path)) {
            return null;
        }
        $checksum = hash('sha256', $disk->get($path));
        if ($existing = Media::withTrashed()->where('checksum', $checksum)->first()) {
            return $existing;
        }
        $absolutePath = $disk->path($path);
        $dimensions = @getimagesize($absolutePath) ?: [null, null];
        $folderName = basename(dirname($path));
        $folder = in_array($folderName, ['players', 'staff', 'clubs', 'competitions', 'matches', 'sponsors', 'general'], true) ? $folderName : 'general';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return Media::create(['uuid' => (string) Str::uuid(), 'disk' => 'public', 'directory' => dirname($path), 'filename' => basename($path), 'original_name' => basename($path), 'extension' => $extension, 'mime_type' => mime_content_type($absolutePath) ?: "image/{$extension}", 'size_bytes' => $disk->size($path), 'width' => $dimensions[0], 'height' => $dimensions[1], 'checksum' => $checksum, 'title' => pathinfo($path, PATHINFO_FILENAME), 'folder' => $folder, 'uploaded_by' => $user?->id]);
    }

    public function syncUsages(Model $consumer, array $fields): void
    {
        MediaUsage::where('usable_type', $consumer->getMorphClass())->where('usable_id', $consumer->getKey())->whereIn('field_key', array_keys($fields))->delete();
        foreach ($fields as $field => $url) {
            if (! is_string($url) || ! str_contains($url, '/storage/')) {
                continue;
            }
            $path = ltrim(Str::after($url, '/storage/'), '/');
            $media = Media::where('directory', dirname($path))->where('filename', basename($path))->first();
            if ($media) {
                MediaUsage::firstOrCreate(['media_id' => $media->id, 'usable_type' => $consumer->getMorphClass(), 'usable_id' => $consumer->getKey(), 'field_key' => $field]);
            }
        }
    }
}
