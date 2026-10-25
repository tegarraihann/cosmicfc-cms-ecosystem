<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', fn (Blueprint $t) => tap($t, function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->boolean('is_system')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        }));
        Schema::create('permissions', fn (Blueprint $t) => tap($t, function ($t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('group');
            $t->text('description')->nullable();
            $t->timestamps();
        }));
        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('role_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $t->boolean('is_active')->default(true)->after('password');
            $t->timestamp('last_login_at')->nullable()->after('is_active');
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('role_id');
            $t->dropColumn(['is_active', 'last_login_at', 'deleted_at']);
        });
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
