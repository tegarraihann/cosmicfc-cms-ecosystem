<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_profiles', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['draft', 'published'])->unique();
            $table->unsignedInteger('version')->default(1);
            $table->string('club_name');
            $table->string('short_name');
            $table->string('tagline')->nullable();
            $table->text('short_description')->nullable();
            $table->date('founded_at')->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('home_base')->nullable();
            $table->text('address')->nullable();
            $table->string('map_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('alternate_logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('share_image_url')->nullable();
            $table->string('primary_color', 7)->default('#07111e');
            $table->string('accent_color', 7)->default('#45b7ff');
            $table->json('socials')->nullable();
            $table->json('seo')->nullable();
            $table->json('website')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        Schema::create('club_profile_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_profile_audits');
        Schema::dropIfExists('club_profiles');
    }
};
