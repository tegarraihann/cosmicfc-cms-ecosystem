<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('match_games', function (Blueprint $table) {
            $table->id();
            $table->string('opponent');
            $table->dateTime('kickoff_at');
            $table->string('venue');
            $table->string('competition')->nullable();
            $table->string('status')->default('scheduled');
            $table->unsignedTinyInteger('cosmic_score')->nullable();
            $table->unsignedTinyInteger('opponent_score')->nullable();
            $table->json('match_events')->nullable();
            $table->string('cover_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_games');
    }
};
