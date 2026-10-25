<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 90)->unique();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->enum('type', ['general', 'match_report'])->default('general')->index();
            $table->foreignId('category_id')->constrained('news_categories')->restrictOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('title', 150);
            $table->string('excerpt', 220);
            $table->longText('body');
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 220)->nullable();
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('news_article_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('caption')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['news_article_id', 'media_id']);
        });
        Schema::create('news_article_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_article_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamps();
        });

        foreach (['Laporan Pertandingan', 'Kabar Klub', 'Pengumuman', 'Komunitas', 'Behind The Scenes'] as $index => $name) {
            DB::table('news_categories')->insert(['name' => $name, 'slug' => Str::slug($name), 'display_order' => ($index + 1) * 10, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $permissions = ['news.view' => 'Lihat Berita', 'news.create' => 'Tambah Berita', 'news.update' => 'Ubah Berita', 'news.publish' => 'Publikasikan Berita', 'news.archive' => 'Arsipkan Berita', 'news.categories' => 'Kelola Kategori Berita'];
        foreach ($permissions as $slug => $name) {
            $permissionId = DB::table('permissions')->insertGetId(['name' => $name, 'slug' => $slug, 'group' => 'Berita & Dokumentasi', 'created_at' => now(), 'updated_at' => now()]);
            $roleIds = DB::table('roles')->whereIn('slug', ['superadmin', 'admin'])->pluck('id');
            foreach ($roleIds as $roleId) {
                DB::table('permission_role')->insertOrIgnore(['permission_id' => $permissionId, 'role_id' => $roleId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('news_article_audits');
        Schema::dropIfExists('news_article_media');
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('news_categories');
    }
};
