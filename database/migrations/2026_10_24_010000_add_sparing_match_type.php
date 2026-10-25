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
            // String keeps future operational match types extensible while
            // application validation remains the source of allowed values.
            $table->string('match_type', 30)->default('competitive')->change();
        });

        Schema::table('player_awards', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('player_awards')->whereNull('season_id')->delete();

        Schema::table('player_awards', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')->nullable(false)->change();
        });

        DB::table('matches')->where('match_type', 'sparing')->update(['match_type' => 'friendly']);
        Schema::table('matches', function (Blueprint $table) {
            $table->enum('match_type', ['competitive', 'friendly', 'tournament', 'exhibition'])->default('competitive')->change();
        });
    }
};
