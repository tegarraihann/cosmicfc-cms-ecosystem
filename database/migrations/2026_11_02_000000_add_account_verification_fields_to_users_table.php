<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_email')->nullable()->unique()->after('email');
            $table->string('email_change_token')->nullable()->after('email_verified_at');
            $table->timestamp('email_change_expires_at')->nullable()->after('email_change_token');
            $table->boolean('must_change_email')->default(false)->after('email_change_expires_at');
            $table->string('invitation_token')->nullable()->after('must_change_email');
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_token');
            $table->timestamp('invited_at')->nullable()->after('invitation_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pending_email']);
            $table->dropColumn([
                'pending_email',
                'email_change_token',
                'email_change_expires_at',
                'must_change_email',
                'invitation_token',
                'invitation_expires_at',
                'invited_at',
            ]);
        });
    }
};
