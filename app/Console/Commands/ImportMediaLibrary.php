<?php

namespace App\Console\Commands;

use App\Services\MediaLibraryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportMediaLibrary extends Command
{
    protected $signature = 'media:import {--dry-run : Hanya hitung file tanpa menyimpan}';

    protected $description = 'Import aset gambar lama dari storage publik ke Media Library';

    public function handle(MediaLibraryService $service): int
    {
        $files = collect(Storage::disk('public')->allFiles('uploads'))->filter(fn ($path) => preg_match('/\.(jpe?g|png|webp)$/i', $path));
        if ($this->option('dry-run')) {
            $this->info("{$files->count()} file siap diimport.");

            return self::SUCCESS;
        }
        $imported = 0;
        $files->each(function ($path) use ($service, &$imported) {
            if ($service->import($path)) {
                $imported++;
            }
        });
        $this->info("Import selesai. {$imported} file diproses tanpa duplikasi.");

        return self::SUCCESS;
    }
}
