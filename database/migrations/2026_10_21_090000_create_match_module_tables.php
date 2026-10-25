<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('match_type', ['competitive', 'friendly', 'tournament', 'exhibition'])->default('competitive')->index();
            $table->unsignedSmallInteger('matchday')->nullable();
            $table->string('round_name')->nullable();
            $table->foreignId('home_club_id')->constrained('clubs')->restrictOnDelete();
            $table->foreignId('away_club_id')->constrained('clubs')->restrictOnDelete();
            $table->dateTime('kickoff_at')->nullable()->index();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'live', 'completed', 'postponed', 'cancelled', 'walkover'])->default('draft')->index();
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->boolean('count_for_standings')->default(true);
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_public')->default(false)->index();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('short_report')->nullable();
            $table->unsignedInteger('attendance')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['season_id', 'matchday']);
            $table->index(['home_club_id', 'away_club_id', 'kickoff_at']);
        });
        Schema::create('match_lineups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('club_id')->constrained('clubs')->restrictOnDelete();
            $table->foreignId('player_id')->nullable()->constrained('players')->restrictOnDelete();
            $table->string('guest_name')->nullable();
            $table->enum('role', ['starter', 'substitute'])->default('starter');
            $table->string('position')->nullable();
            $table->unsignedTinyInteger('shirt_number')->nullable();
            $table->boolean('is_captain')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['match_id', 'club_id', 'player_id']);
        });
        Schema::create('match_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_audits');
        Schema::dropIfExists('match_lineups');
        Schema::dropIfExists('matches');
    }
};
