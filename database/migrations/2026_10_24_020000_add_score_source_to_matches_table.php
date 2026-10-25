<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('score_mode', 20)->default('automatic')->after('status');
            $table->text('manual_score_reason')->nullable()->after('away_score');
            $table->unsignedSmallInteger('home_penalty_score')->nullable()->after('away_score');
            $table->unsignedSmallInteger('away_penalty_score')->nullable()->after('home_penalty_score');
        });

        DB::table('matches')
            ->whereIn('status', ['completed', 'walkover'])
            ->update([
                'score_mode' => 'manual',
                'manual_score_reason' => 'Migrasi data lama: skor pertandingan dipertahankan sebagai skor manual.',
            ]);
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['score_mode', 'manual_score_reason', 'home_penalty_score', 'away_penalty_score']);
        });
    }
};
