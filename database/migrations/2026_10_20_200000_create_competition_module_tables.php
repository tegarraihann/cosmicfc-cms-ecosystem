<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('short_name', 20);
            $table->enum('type', ['league', 'tournament', 'friendly_series', 'single_event'])->default('league')->index();
            $table->string('organizer')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('banner_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('website_url', 500)->nullable();
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::table('seasons', function (Blueprint $table) {
            $table->foreignId('competition_id')->nullable()->after('id')->constrained('competitions')->nullOnDelete();
            $table->string('slug')->nullable()->unique()->after('name');
            $table->enum('format', ['league', 'group', 'knockout', 'hybrid', 'friendly'])->default('league');
            $table->unsignedSmallInteger('current_matchday')->default(0);
            $table->unsignedSmallInteger('total_matchdays')->default(0);
            $table->unsignedSmallInteger('max_participants')->nullable();
            $table->json('scoring_rules')->nullable();
            $table->json('regulations')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->enum('status', ['draft', 'registration', 'scheduled', 'ongoing', 'completed', 'cancelled'])->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::create('competition_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('club_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('seed')->nullable();
            $table->string('group_name', 20)->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->date('joined_at')->nullable();
            $table->enum('status', ['active', 'withdrawn', 'disqualified'])->default('active');
            $table->timestamps();
            $table->unique(['season_id', 'club_id']);
        });
        Schema::create('competition_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_audits');
        Schema::dropIfExists('competition_participants');
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['slug', 'format', 'current_matchday', 'total_matchdays', 'max_participants', 'scoring_rules', 'regulations', 'is_featured', 'status', 'published_at']);
        });
        Schema::dropIfExists('competitions');
    }
};
