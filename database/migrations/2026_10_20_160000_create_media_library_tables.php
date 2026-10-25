<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('disk')->default('public');
            $table->string('directory');
            $table->string('filename');
            $table->string('original_name');
            $table->string('extension', 12);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('checksum', 64)->index();
            $table->string('title');
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->enum('folder', ['general', 'players', 'matches', 'sponsors'])->default('general')->index();
            $table->enum('status', ['active', 'archived'])->default('active')->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['disk', 'directory', 'filename']);
        });

        Schema::create('media_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('media_media_tag', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('media_tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->primary(['media_id', 'media_tag_id']);
        });

        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->nullableMorphs('usable');
            $table->string('field_key');
            $table->timestamps();
            $table->unique(['media_id', 'usable_type', 'usable_id', 'field_key'], 'media_usage_unique');
        });

        Schema::create('media_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_audits');
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_media_tag');
        Schema::dropIfExists('media_tags');
        Schema::dropIfExists('media');
    }
};
