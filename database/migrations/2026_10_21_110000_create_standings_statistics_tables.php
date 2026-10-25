<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standing_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->integer('points');
            $table->string('reason');
            $table->string('reference')->nullable();
            $table->unsignedSmallInteger('effective_matchday')->default(1);
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('standing_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('matchday');
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('won')->default(0);
            $table->unsignedSmallInteger('drawn')->default(0);
            $table->unsignedSmallInteger('lost')->default(0);
            $table->unsignedSmallInteger('goals_for')->default(0);
            $table->unsignedSmallInteger('goals_against')->default(0);
            $table->integer('goal_difference')->default(0);
            $table->integer('base_points')->default(0);
            $table->integer('adjustment_points')->default(0);
            $table->integer('points')->default(0);
            $table->json('form')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('calculated_at');
            $table->string('calculation_hash', 64);
            $table->timestamps();
            $table->unique(['season_id', 'matchday', 'club_id']);
        });

        Schema::create('statistic_recalculation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('trigger', 60);
            $table->string('status', 30);
            $table->string('input_hash', 64)->nullable();
            $table->json('summary')->nullable();
            $table->json('errors')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistic_recalculation_logs');
        Schema::dropIfExists('standing_snapshots');
        Schema::dropIfExists('standing_adjustments');
    }
};
