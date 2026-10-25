<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_mvp_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('result_impact');
            $table->unsignedTinyInteger('position_performance');
            $table->unsignedTinyInteger('consistency');
            $table->unsignedTinyInteger('crucial_moments');
            $table->unsignedTinyInteger('discipline_teamwork');
            $table->decimal('total_score', 5, 2);
            $table->json('evidence_snapshot')->nullable();
            $table->text('assessment_note');
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();

            $table->unique(['match_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_mvp_assessments');
    }
};
