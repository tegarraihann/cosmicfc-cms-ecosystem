<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_captain');
            $table->unsignedTinyInteger('featured_order')->nullable()->after('is_featured');
            $table->unsignedSmallInteger('display_order')->default(0)->after('featured_order');
        });

        if (Schema::hasTable('player_season')) {
            DB::table('players')->orderBy('id')->each(function ($player) {
                $membership = DB::table('player_season')
                    ->where('player_id', $player->id)
                    ->latest('id')
                    ->first();

                if ($membership) {
                    DB::table('players')->where('id', $player->id)->update([
                        'is_featured' => (bool) $membership->is_featured,
                        'featured_order' => $membership->featured_order,
                        'display_order' => $membership->display_order,
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'featured_order', 'display_order']);
        });
    }
};
