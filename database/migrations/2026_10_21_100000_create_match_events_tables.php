<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->string('period', 30)->default('second_half');
            $table->unsignedSmallInteger('minute');
            $table->unsignedSmallInteger('added_minute')->default(0);
            $table->foreignId('primary_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('primary_player_name')->nullable();
            $table->foreignId('secondary_player_id')->nullable()->constrained('players')->nullOnDelete();
            $table->string('secondary_player_name')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_score_event')->default(false);
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['match_id', 'minute', 'added_minute', 'sort_order']);
            $table->index(['primary_player_id', 'type']);
        });

        Schema::create('match_event_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_event_id')->nullable()->constrained('match_events')->nullOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_event_audits');
        Schema::dropIfExists('match_events');
    }
};
