<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'analytics.view' => 'Lihat Statistik Pengunjung',
            'analytics.refresh' => 'Perbarui Statistik GA4',
            'analytics.configure' => 'Konfigurasi Google Analytics 4',
        ];

        foreach ($permissions as $slug => $name) {
            DB::table('permissions')->updateOrInsert(['slug' => $slug], ['name' => $name, 'group' => 'Statistik Pengunjung', 'updated_at' => now(), 'created_at' => now()]);
        }

        $permissionIds = DB::table('permissions')->whereIn('slug', array_keys($permissions))->pluck('id');
        $roles = DB::table('roles')->whereIn('slug', ['superadmin', 'admin'])->get(['id', 'slug']);
        foreach ($roles as $role) {
            foreach ($permissionIds as $permissionId) {
                $slug = DB::table('permissions')->where('id', $permissionId)->value('slug');
                if ($role->slug === 'admin' && $slug === 'analytics.configure') {
                    continue;
                }
                DB::table('permission_role')->updateOrInsert(['role_id' => $role->id, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('slug', ['analytics.view', 'analytics.refresh', 'analytics.configure'])->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
