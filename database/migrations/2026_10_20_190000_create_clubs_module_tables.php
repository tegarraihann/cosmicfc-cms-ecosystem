<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('official_name');
            $table->string('display_name');
            $table->string('short_name', 12);
            $table->enum('type', ['internal', 'opponent', 'community', 'guest'])->default('opponent')->index();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->default('Indonesia');
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->foreignId('primary_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('alternate_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('primary_color', 7)->default('#45B7FF');
            $table->string('secondary_color', 7)->default('#FFFFFF');
            $table->string('website_url', 500)->nullable();
            $table->string('instagram_url', 500)->nullable();
            $table->string('contact_email', 160)->nullable();
            $table->string('default_venue', 180)->nullable();
            $table->boolean('is_cosmic')->default(false)->index();
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['display_name', 'short_name']);
        });
        Schema::create('club_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->string('alias');
            $table->string('normalized_alias')->unique();
            $table->string('source')->default('manual');
            $table->timestamps();
        });
        Schema::create('club_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_audits');
        Schema::dropIfExists('club_aliases');
        Schema::dropIfExists('clubs');
    }
};
