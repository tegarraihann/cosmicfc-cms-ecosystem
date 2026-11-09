<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\ClubProfile;
use App\Models\PageLayout;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ClubProfileService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $groups = [
            'Dashboard' => ['dashboard.view' => 'Lihat Dashboard'],
            'Statistik Pengunjung' => ['analytics.view' => 'Lihat Statistik Pengunjung', 'analytics.refresh' => 'Perbarui Statistik GA4', 'analytics.configure' => 'Konfigurasi Google Analytics 4'],
            'Pengguna' => ['users.view' => 'Lihat Pengguna', 'users.create' => 'Tambah Pengguna', 'users.update' => 'Ubah Pengguna', 'users.disable' => 'Nonaktifkan Pengguna'],
            'Role' => ['roles.view' => 'Lihat Role', 'roles.create' => 'Tambah Role', 'roles.update' => 'Ubah Role', 'roles.delete' => 'Hapus Role'],
            'Landing Page' => ['landing.view' => 'Lihat Landing Page', 'landing.update' => 'Ubah Landing Page', 'landing.preview' => 'Preview Landing Page', 'landing.publish' => 'Publikasikan Landing Page'],
            'Pemain' => ['players.view' => 'Lihat Pemain', 'players.create' => 'Tambah Pemain', 'players.update' => 'Ubah Pemain', 'players.stats' => 'Ubah Statistik Pemain', 'players.awards' => 'Kelola Penghargaan Pemain', 'players.delete' => 'Hapus Pemain'],
            'Staff & Officials' => ['staff.view' => 'Lihat Staff', 'staff.create' => 'Tambah Staff', 'staff.update' => 'Ubah Staff', 'staff.assign' => 'Kelola Penugasan Staff', 'staff.delete' => 'Hapus Staff'],
            'Klub & Lawan' => ['clubs.view' => 'Lihat Klub', 'clubs.create' => 'Tambah Klub', 'clubs.update' => 'Ubah Klub', 'clubs.archive' => 'Arsipkan Klub', 'clubs.delete' => 'Hapus Klub'],
            'Kompetisi & Musim' => ['competitions.view' => 'Lihat Kompetisi', 'competitions.create' => 'Tambah Kompetisi', 'competitions.update' => 'Ubah Kompetisi dan Musim', 'competitions.participants' => 'Kelola Peserta', 'competitions.publish' => 'Publikasikan Musim', 'competitions.delete' => 'Hapus Kompetisi'],
            'Pertandingan' => ['matches.view' => 'Lihat Pertandingan', 'matches.create' => 'Tambah Pertandingan', 'matches.update' => 'Ubah Pertandingan', 'matches.results' => 'Kelola Hasil', 'matches.lineups' => 'Kelola Lineup', 'matches.publish' => 'Publikasikan Pertandingan', 'matches.correct' => 'Koreksi Hasil', 'matches.delete' => 'Hapus Pertandingan'],
            'Match Events' => ['match-events.view' => 'Lihat Match Events', 'match-events.create' => 'Tambah Match Event', 'match-events.update' => 'Ubah Match Event', 'match-events.delete' => 'Hapus Match Event', 'match-events.publish' => 'Publikasikan Match Event', 'match-events.correct' => 'Koreksi Match Event'],
            'Klasemen & Statistik' => ['standings.view' => 'Lihat Klasemen', 'standings.recalculate' => 'Hitung Ulang Klasemen', 'standings.publish' => 'Publikasikan Klasemen', 'standings.adjust' => 'Buat Adjustment Poin', 'standings.approve' => 'Setujui Adjustment Poin', 'standings.export' => 'Ekspor Klasemen'],
            'Player Awards' => ['player-awards.view' => 'Lihat Player Awards', 'player-awards.create' => 'Tambah Player Award', 'player-awards.update' => 'Ubah Player Award', 'player-awards.publish' => 'Publikasikan Player Award', 'player-awards.correct' => 'Koreksi Player Award', 'player-awards.archive' => 'Arsipkan Player Award', 'player-awards.types' => 'Kelola Kategori Award'],
            'Liga' => ['league.view' => 'Lihat Liga', 'league.update' => 'Ubah Liga'],
            'Sponsor & Partners' => ['sponsors.view' => 'Lihat Sponsor', 'sponsors.create' => 'Tambah Sponsor', 'sponsors.update' => 'Ubah Sponsor dan Kerja Sama', 'sponsors.publish' => 'Publikasikan Sponsor', 'sponsors.renew' => 'Perpanjang Kerja Sama', 'sponsors.archive' => 'Arsipkan Sponsor', 'sponsors.delete' => 'Hapus Sponsor'],
            'Berita & Dokumentasi' => ['news.view' => 'Lihat Berita', 'news.create' => 'Tambah Berita', 'news.update' => 'Ubah Berita', 'news.publish' => 'Publikasikan Berita', 'news.archive' => 'Arsipkan Berita', 'news.categories' => 'Kelola Kategori Berita'],
            'Media' => ['media.view' => 'Lihat Media', 'media.upload' => 'Unggah Media', 'media.update' => 'Ubah Metadata Media', 'media.delete' => 'Hapus Media', 'media.restore' => 'Pulihkan Media'],
            'Profil Klub' => ['club-profile.view' => 'Lihat Profil Klub', 'club-profile.update' => 'Ubah Profil Klub', 'club-profile.preview' => 'Preview Profil Klub', 'club-profile.publish' => 'Publikasikan Profil Klub'],
        ];

        foreach ($groups as $group => $permissions) {
            foreach ($permissions as $slug => $name) {
                Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'group' => $group]);
            }
        }

        $superadmin = Role::updateOrCreate(['slug' => 'superadmin'], ['name' => 'Super Administrator', 'description' => 'Akses penuh dan pengelolaan hak akses.', 'is_system' => true, 'is_active' => true]);
        $admin = Role::updateOrCreate(['slug' => 'admin'], ['name' => 'Administrator', 'description' => 'Mengelola konten dan operasional website.', 'is_system' => true, 'is_active' => true]);
        $member = Role::updateOrCreate(['slug' => 'member'], ['name' => 'Member', 'description' => 'Akses anggota terbatas.', 'is_system' => true, 'is_active' => true]);

        $superadmin->permissions()->sync(Permission::pluck('id'));
        $admin->permissions()->sync(Permission::whereNotIn('group', ['Pengguna', 'Role'])
            ->whereNotIn('slug', ['analytics.configure', 'landing.publish', 'club-profile.publish', 'media.restore', 'players.delete', 'staff.delete', 'clubs.delete', 'competitions.delete', 'competitions.publish', 'matches.correct', 'matches.delete', 'match-events.correct', 'standings.approve', 'player-awards.correct', 'player-awards.types', 'sponsors.publish', 'sponsors.delete'])->pluck('id'));
        $member->permissions()->sync(Permission::where('slug', 'dashboard.view')->pluck('id'));

        $profile = app(ClubProfileService::class)->defaults();
        ClubProfile::firstOrCreate(['status' => 'published'], [...$profile, 'published_at' => now()]);
        ClubProfile::firstOrCreate(['status' => 'draft'], $profile);

        foreach (config('page-sections') as $pageKey => $definition) {
            $layout = PageLayout::updateOrCreate(['page_key' => $pageKey], ['label' => $definition['label'], 'path' => $definition['path']]);

            foreach ($definition['sections'] as $index => $section) {
                $pageSection = $layout->sections()->firstOrNew(['section_key' => $section['key']]);
                $pageSection->fill(['label' => $section['label'], 'component' => $section['component'], 'is_locked' => $section['locked'] ?? false]);
                if (! $pageSection->exists) {
                    $order = ($index + 1) * 10;
                    $pageSection->fill(['is_visible' => true, 'sort_order' => $order, 'draft_visible' => true, 'draft_sort_order' => $order]);
                }
                $pageSection->save();
                $defaultContent = config('section-defaults')[$section['key']] ?? [];

                $pageSection->contentVersions()->firstOrCreate(
                    ['status' => 'published'],
                    ['content' => $defaultContent, 'version' => 1],
                );
                $pageSection->contentVersions()->firstOrCreate(
                    ['status' => 'draft'],
                    ['content' => $defaultContent, 'version' => 1],
                );
            }
        }

        $bootstrapSuperadmin = User::where('role', UserRole::SuperAdmin)->first();

        if ($bootstrapSuperadmin) {
            // Deployment berikutnya tidak boleh mengembalikan email/password yang
            // sudah diamankan oleh pemilik akun ke nilai awal dari environment.
            $bootstrapSuperadmin->forceFill([
                'role_id' => $superadmin->id,
                'is_active' => true,
            ])->save();
        } else {
            User::create([
                'name' => 'Cosmic Super Administrator',
                'email' => env('SUPERADMIN_EMAIL', 'superadmin@cosmic.test'),
                'role' => UserRole::SuperAdmin,
                'role_id' => $superadmin->id,
                'email_verified_at' => now(),
                'must_change_email' => true,
                'is_active' => true,
                'password' => env('SUPERADMIN_PASSWORD', 'CosmicSuperAdmin2026!'),
            ]);
        }
    }
}
