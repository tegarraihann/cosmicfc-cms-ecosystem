<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('page_key')->unique();
            $table->string('label');
            $table->string('path');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('has_unpublished_changes')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_layout_id')->constrained()->cascadeOnDelete();
            $table->string('section_key');
            $table->string('label');
            $table->string('component');
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order');
            $table->boolean('draft_visible')->default(true);
            $table->unsignedInteger('draft_sort_order');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['page_layout_id', 'section_key']);
        });
        Schema::create('page_layout_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_layout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_layout_audits');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('page_layouts');
    }
};
