<?php

namespace App\Services;

use App\Models\Club;
use App\Models\GameMatch;
use App\Models\Media;
use App\Models\Player;
use App\Models\Season;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    public function data(User $user): array
    {
        return [
            'stats' => $this->stats(),
            'nextMatch' => $this->nextMatch(),
            'activities' => $this->activities(),
            'shortcuts' => $this->shortcuts($user),
        ];
    }

    private function stats(): array
    {
        $upcoming = GameMatch::query()->whereIn('status', ['scheduled', 'live'])->where('kickoff_at', '>=', now())->count();
        $completedThisMonth = GameMatch::query()->whereIn('status', ['completed', 'walkover'])->whereBetween('kickoff_at', [now()->startOfMonth(), now()->endOfMonth()])->count();

        return [
            ['value' => Player::query()->where('status', 'active')->count(), 'label' => 'Pemain Aktif', 'note' => 'Terdaftar dalam skuad', 'icon' => 'users'],
            ['value' => $upcoming, 'label' => 'Laga Mendatang', 'note' => $completedThisMonth.' laga selesai bulan ini', 'icon' => 'calendar'],
            ['value' => Club::query()->where('status', 'active')->count(), 'label' => 'Klub Aktif', 'note' => Season::query()->where('is_active', true)->count().' musim sedang berjalan', 'icon' => 'trophy'],
            ['value' => Media::query()->where('status', 'active')->count(), 'label' => 'Media Tersimpan', 'note' => 'Foto dan dokumentasi aktif', 'icon' => 'image'],
        ];
    }

    private function nextMatch(): ?array
    {
        $match = GameMatch::query()
            ->with(['homeClub:id,display_name', 'awayClub:id,display_name'])
            ->whereIn('status', ['scheduled', 'live'])
            ->where('kickoff_at', '>=', now())
            ->orderBy('kickoff_at')
            ->first();

        if (! $match) {
            return null;
        }

        return [
            'teams' => ($match->homeClub?->display_name ?? 'Tuan rumah').' vs '.($match->awayClub?->display_name ?? 'Lawan'),
            'date' => $match->kickoff_at?->locale('id')->translatedFormat('d M Y'),
            'time' => $match->kickoff_at?->format('H.i').' WIB',
            'venue' => $match->venue_name ?: 'Lokasi belum ditentukan',
            'href' => route('admin.matches.index'),
        ];
    }

    private function activities(): array
    {
        $sources = [
            ['table' => 'match_audits', 'module' => 'Pertandingan', 'type' => 'match', 'href' => route('admin.matches.index')],
            ['table' => 'player_audits', 'module' => 'Pemain', 'type' => 'player', 'href' => route('admin.players.index')],
            ['table' => 'media_audits', 'module' => 'Media', 'type' => 'media', 'href' => route('admin.media.index')],
            ['table' => 'competition_audits', 'module' => 'Kompetisi & Musim', 'type' => 'competition', 'href' => route('admin.competitions.index')],
            ['table' => 'club_audits', 'module' => 'Klub & Lawan', 'type' => 'club', 'href' => route('admin.clubs.index')],
            ['table' => 'staff_audits', 'module' => 'Staff & Officials', 'type' => 'staff', 'href' => route('admin.staff.index')],
            ['table' => 'partner_audits', 'module' => 'Sponsor & Partners', 'type' => 'sponsor', 'href' => route('admin.sponsors.index')],
            ['table' => 'page_layout_audits', 'module' => 'Landing Page', 'type' => 'content', 'href' => route('admin.pages.index')],
            ['table' => 'club_profile_audits', 'module' => 'Pengaturan Website', 'type' => 'content', 'href' => route('admin.club-profile.edit')],
            ['table' => 'player_award_audits', 'module' => 'Player Awards', 'type' => 'player', 'href' => route('admin.player-awards.index')],
            ['table' => 'news_article_audits', 'module' => 'Berita & Dokumentasi', 'type' => 'content', 'href' => route('admin.news.index')],
        ];

        return collect($sources)
            ->filter(fn (array $source) => Schema::hasTable($source['table']))
            ->flatMap(fn (array $source) => $this->sourceActivities($source))
            ->sortByDesc('timestamp')
            ->take(8)
            ->values()
            ->map(fn (array $activity) => collect($activity)->except('timestamp')->all())
            ->all();
    }

    private function sourceActivities(array $source): Collection
    {
        return DB::table($source['table'].' as audits')
            ->leftJoin('users', 'users.id', '=', 'audits.user_id')
            ->select(['audits.id', 'audits.action', 'audits.created_at', 'users.name as user_name'])
            ->latest('audits.created_at')
            ->limit(8)
            ->get()
            ->map(function ($audit) use ($source) {
                $createdAt = Carbon::parse($audit->created_at);

                return [
                    'id' => $source['table'].'-'.$audit->id,
                    'title' => $this->activityTitle($source['module'], $audit->action),
                    'detail' => ($audit->user_name ?: 'Sistem').' · '.$source['module'],
                    'time' => $createdAt->locale('id')->diffForHumans(),
                    'type' => $source['type'],
                    'href' => $source['href'],
                    'timestamp' => $createdAt->timestamp,
                ];
            });
    }

    private function activityTitle(string $module, string $action): string
    {
        $verb = match ($action) {
            'created', 'season_created' => 'ditambahkan',
            'updated', 'corrected', 'season_updated', 'lineups_updated', 'participants_synced', 'draft_saved' => 'diperbarui',
            'published' => 'dipublikasikan',
            'draft_discarded' => 'dikembalikan ke versi publik',
            'deleted' => 'dihapus',
            'archived' => 'diarsipkan',
            'uploaded' => 'diunggah',
            'restored' => 'dipulihkan',
            default => 'diperbarui',
        };

        return $module.' '.$verb;
    }

    private function shortcuts(User $user): array
    {
        return collect([
            ['label' => 'Tambah Pertandingan', 'description' => 'Buat jadwal atau hasil pertandingan', 'href' => route('admin.matches.index', ['action' => 'create']), 'icon' => 'plus', 'permission' => 'matches.create'],
            ['label' => 'Tambah Pemain', 'description' => 'Tambahkan anggota baru ke skuad', 'href' => route('admin.players.index', ['action' => 'create']), 'icon' => 'plus', 'permission' => 'players.create'],
            ['label' => 'Upload Media', 'description' => 'Unggah foto atau dokumentasi', 'href' => route('admin.media.index', ['action' => 'upload']).'#upload-media', 'icon' => 'upload', 'permission' => 'media.upload'],
            ['label' => 'Kelola Landing Page', 'description' => 'Atur section halaman publik', 'href' => route('admin.pages.index'), 'icon' => 'layout', 'permission' => 'landing.view'],
            ['label' => 'Tambah Berita', 'description' => 'Dokumentasikan kabar atau hasil laga', 'href' => route('admin.news.create'), 'icon' => 'plus', 'permission' => 'news.create'],
        ])->filter(fn (array $shortcut) => $user->hasPermission($shortcut['permission']))
            ->map(fn (array $shortcut) => collect($shortcut)->except('permission')->all())
            ->values()
            ->all();
    }
}
