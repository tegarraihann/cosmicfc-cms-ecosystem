<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_minutes')->default(120)->after('kickoff_at');
            $table->timestamp('expected_end_at')->nullable()->after('duration_minutes')->index();
            $table->timestamp('result_finalized_at')->nullable()->after('expected_end_at');
        });

        DB::table('matches')->whereNotNull('kickoff_at')->orderBy('id')->chunkById(100, function ($matches): void {
            foreach ($matches as $match) {
                DB::table('matches')->where('id', $match->id)->update([
                    'expected_end_at' => Carbon::parse($match->kickoff_at)->addMinutes((int) ($match->duration_minutes ?: 120)),
                ]);
            }
        });

        Schema::create('match_admin_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('area', 30)->default('result');
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();
            $table->unique(['match_id', 'user_id', 'area']);
            $table->index(['match_id', 'last_seen_at']);
        });

        Schema::create('match_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('notification_type', 60);
            $table->string('recipient')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('idempotency_key')->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->timestamps();
            $table->index(['match_id', 'notification_type', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_notification_logs');
        Schema::dropIfExists('match_admin_presences');
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex(['expected_end_at']);
            $table->dropColumn(['duration_minutes', 'expected_end_at', 'result_finalized_at']);
        });
    }
};
