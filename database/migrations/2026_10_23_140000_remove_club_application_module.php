<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('application_files')) {
            DB::table('application_files')->select(['disk', 'path'])->orderBy('id')->each(function ($file): void {
                if ($file->path && Storage::disk($file->disk ?: 'local')->exists($file->path)) {
                    Storage::disk($file->disk ?: 'local')->delete($file->path);
                }
            });
        }

        Schema::dropIfExists('application_files');
        Schema::dropIfExists('application_audits');
        Schema::dropIfExists('application_notes');
        Schema::dropIfExists('application_trials');
        Schema::dropIfExists('application_status_logs');
        Schema::dropIfExists('club_applications');
        Schema::dropIfExists('club_application_settings');

        if (Schema::hasTable('page_sections')) {
            DB::table('page_sections')->whereIn('section_key', [
                'schedule.cta',
                'league.cta',
                'team.cta',
            ])->delete();
        }

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('slug', 'like', 'applications.%')->delete();
        }
    }

    public function down(): void
    {
        // Modul dan data pribadi pendaftar sengaja tidak dapat dipulihkan lewat rollback.
    }
};
