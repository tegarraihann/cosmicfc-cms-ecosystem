<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seasons')) {
            Schema::create('seasons', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('is_active')->default(false)->index();
                $table->timestamps();
            });
        }
        Schema::table('players', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique();
            $table->string('full_name')->nullable();
            $table->string('display_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->enum('dominant_foot', ['Kanan', 'Kiri', 'Keduanya'])->nullable();
            $table->string('hometown')->nullable();
            $table->foreignId('primary_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('alternate_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->unsignedTinyInteger('image_focal_x')->default(50);
            $table->unsignedTinyInteger('image_focal_y')->default(40);
            $table->enum('status', ['draft', 'active', 'inactive', 'alumni'])->default('draft')->index();
            $table->json('seo')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
        if (! Schema::hasTable('player_season')) {
            Schema::create('player_season', function (Blueprint $table) {
                $table->id();
                $table->foreignId('player_id')->constrained()->cascadeOnDelete();
                $table->foreignId('season_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('shirt_number');
                $table->enum('primary_position', ['Goalkeeper', 'Defender', 'Midfielder', 'Forward']);
                $table->string('secondary_position')->nullable();
                $table->boolean('is_captain')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->unsignedTinyInteger('featured_order')->nullable();
                $table->enum('squad_status', ['active', 'injured', 'suspended', 'inactive'])->default('active');
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamps();
                $table->unique(['season_id', 'shirt_number']);
                $table->unique(['player_id', 'season_id']);
            });
        }
        if (! Schema::hasTable('player_season_stats')) {
            Schema::create('player_season_stats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('player_id')->constrained()->cascadeOnDelete();
                $table->foreignId('season_id')->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('appearances')->default(0);
                $table->unsignedSmallInteger('starts')->default(0);
                $table->unsignedInteger('minutes')->default(0);
                $table->unsignedSmallInteger('goals')->default(0);
                $table->unsignedSmallInteger('assists')->default(0);
                $table->unsignedSmallInteger('clean_sheets')->default(0);
                $table->unsignedSmallInteger('yellow_cards')->default(0);
                $table->unsignedSmallInteger('red_cards')->default(0);
                $table->decimal('rating', 3, 1)->nullable();
                $table->unsignedTinyInteger('attendance_percent')->nullable();
                $table->timestamps();
                $table->unique(['player_id', 'season_id']);
            });
        }
        if (! Schema::hasTable('player_awards')) {
            Schema::create('player_awards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('player_id')->constrained()->cascadeOnDelete();
                $table->foreignId('season_id')->constrained()->cascadeOnDelete();
                $table->enum('type', ['player_of_month', 'motm', 'custom'])->default('player_of_month');
                $table->string('period_month', 7)->nullable()->index();
                $table->string('title');
                $table->text('editorial')->nullable();
                $table->unsignedSmallInteger('appearances')->default(0);
                $table->unsignedSmallInteger('goals')->default(0);
                $table->unsignedSmallInteger('assists')->default(0);
                $table->decimal('rating', 3, 1)->nullable();
                $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->boolean('is_published')->default(false);
                $table->timestamps();
                $table->unique(['type', 'period_month']);
            });
        }
        if (! Schema::hasTable('player_audits')) {
            Schema::create('player_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->json('before_payload')->nullable();
                $table->json('after_payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('player_audits');
        Schema::dropIfExists('player_awards');
        Schema::dropIfExists('player_season_stats');
        Schema::dropIfExists('player_season');
        Schema::table('players', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_media_id');
            $table->dropConstrainedForeignId('alternate_media_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['uuid', 'full_name', 'display_name', 'birth_date', 'height_cm', 'dominant_foot', 'hometown', 'image_focal_x', 'image_focal_y', 'status', 'seo', 'deleted_at']);
        });
        Schema::dropIfExists('seasons');
    }
};
