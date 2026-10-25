<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('category')->default('supporting')->after('slug');
            $table->text('description')->nullable()->after('category');
            $table->string('contact_name')->nullable()->after('website_url');
            $table->string('contact_email')->nullable()->after('contact_name');
            $table->string('instagram_url')->nullable()->after('contact_email');
            $table->foreignId('logo_media_id')->nullable()->after('logo_path')->constrained('media')->nullOnDelete();
            $table->foreignId('light_logo_media_id')->nullable()->after('logo_media_id')->constrained('media')->nullOnDelete();
            $table->foreignId('dark_logo_media_id')->nullable()->after('light_logo_media_id')->constrained('media')->nullOnDelete();
            $table->string('status')->default('active')->after('is_active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });

        Schema::create('partnerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tier')->default('official');
            $table->string('title')->nullable();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->text('internal_notes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['sponsor_id', 'starts_at', 'ends_at']);
        });

        Schema::create('partner_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partnership_id')->constrained()->cascadeOnDelete();
            $table->string('placement_key');
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['partnership_id', 'placement_key']);
        });

        Schema::create('partner_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('partnership_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_audits');
        Schema::dropIfExists('partner_placements');
        Schema::dropIfExists('partnerships');
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('logo_media_id');
            $table->dropConstrainedForeignId('light_logo_media_id');
            $table->dropConstrainedForeignId('dark_logo_media_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['slug', 'category', 'description', 'contact_name', 'contact_email', 'instagram_url', 'status', 'deleted_at']);
        });
    }
};
