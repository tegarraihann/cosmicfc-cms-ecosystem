<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('period_type', 20);
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::table('player_awards', function (Blueprint $table) {
            $table->dropUnique(['type', 'period_month']);
            $table->foreignId('award_type_id')->nullable()->after('id')->constrained('award_types')->restrictOnDelete();
            $table->foreignId('match_id')->nullable()->after('season_id')->constrained('matches')->nullOnDelete();
            $table->string('period_key', 30)->nullable()->after('period_month')->index();
            $table->string('quote', 500)->nullable()->after('editorial');
            $table->json('statistics_snapshot')->nullable()->after('quote');
            $table->foreignId('primary_media_id')->nullable()->after('media_id')->constrained('media')->nullOnDelete();
            $table->string('status', 20)->default('draft')->after('is_published');
            $table->boolean('is_featured')->default(false)->after('status');
            $table->timestamp('published_at')->nullable()->after('is_featured');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('award_nominees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_award_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->json('statistics_snapshot')->nullable();
            $table->text('nomination_note')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['player_award_id', 'player_id']);
        });

        Schema::create('player_award_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_award_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 50);
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->text('correction_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_award_audits');
        Schema::dropIfExists('award_nominees');
        Schema::table('player_awards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('award_type_id');
            $table->dropConstrainedForeignId('match_id');
            $table->dropConstrainedForeignId('primary_media_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['period_key', 'quote', 'statistics_snapshot', 'status', 'is_featured', 'published_at']);
            $table->unique(['type', 'period_month']);
        });
        Schema::dropIfExists('award_types');
    }
};
