<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_process_runs', function (Blueprint $table) {
            $table->id();
            $table->string('process', 80)->index();
            $table->string('status', 20)->index();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->json('summary')->nullable();
            $table->text('error_summary')->nullable();
            $table->timestamps();
            $table->index(['process', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_process_runs');
    }
};
