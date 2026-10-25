<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->enum('folder', ['general', 'players', 'staff', 'matches', 'sponsors'])->default('general')->change();
        });
    }

    public function down(): void
    {
        DB::table('media')->where('folder', 'staff')->update(['folder' => 'general']);

        Schema::table('media', function (Blueprint $table) {
            $table->enum('folder', ['general', 'players', 'matches', 'sponsors'])->default('general')->change();
        });
    }
};
