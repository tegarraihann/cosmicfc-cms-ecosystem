<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('slug')->unique();
            $t->string('full_name');
            $t->string('display_name');
            $t->string('hometown')->nullable();
            $t->text('biography')->nullable();
            $t->foreignId('primary_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->foreignId('alternate_media_id')->nullable()->constrained('media')->nullOnDelete();
            $t->unsignedTinyInteger('image_focal_x')->default(50);
            $t->unsignedTinyInteger('image_focal_y')->default(40);
            $t->enum('status', ['draft', 'active', 'inactive', 'alumni'])->default('draft')->index();
            $t->json('social_links')->nullable();
            $t->json('seo')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->softDeletes();
            $t->timestamps();
        });
        Schema::create('staff_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $t->string('role_title');
            $t->enum('category', ['leadership', 'official', 'coaching', 'support', 'creative'])->index();
            $t->text('short_description')->nullable();
            $t->string('badge', 12)->nullable();
            $t->date('starts_at')->nullable();
            $t->date('ends_at')->nullable();
            $t->boolean('is_primary_founder')->default(false);
            $t->boolean('is_featured')->default(false);
            $t->unsignedTinyInteger('featured_order')->nullable();
            $t->boolean('is_public')->default(true);
            $t->enum('status', ['scheduled', 'active', 'ended'])->default('active')->index();
            $t->timestamps();
        });
        Schema::create('staff_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action');
            $t->json('before_payload')->nullable();
            $t->json('after_payload')->nullable();
            $t->timestamps();
        });
        Schema::table('club_profiles', function (Blueprint $t) {
            $t->foreignId('founder_staff_id')->nullable()->after('contact_person')->constrained('staff')->nullOnDelete();
            $t->foreignId('contact_staff_id')->nullable()->after('founder_staff_id')->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('club_profiles', function (Blueprint $t) {
            $t->dropConstrainedForeignId('contact_staff_id');
            $t->dropConstrainedForeignId('founder_staff_id');
        });
        Schema::dropIfExists('staff_audits');
        Schema::dropIfExists('staff_assignments');
        Schema::dropIfExists('staff');
    }
};
