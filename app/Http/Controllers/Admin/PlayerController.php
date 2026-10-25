<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AwardType;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Player;
use App\Models\PlayerAudit;
use App\Models\PlayerAward;
use App\Models\Season;
use App\Services\PlayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(Request $r): Response
    {
        $s = Season::where('is_active', true)->first();
        $search = $r->string('search')->trim();
        $position = $r->string('position')->toString();
        $status = $r->string('status')->toString();
        $q = Player::with(['photo', 'stats' => fn ($q) => $q->when($s, fn ($q) => $q->where('season_id', $s->id))->when(! $s, fn ($q) => $q->whereRaw('1 = 0'))]);
        if ($search->isNotEmpty()) {
            $q->where(fn ($q) => $q->where('display_name', 'like', "%{$search}%")->orWhere('full_name', 'like', "%{$search}%"));
        }if ($status) {
            $q->where('status', $status);
        }if ($position) {
            $q->where('position', $position);
        }

        return Inertia::render('Admin/Players', ['players' => $q->latest()->paginate(20)->withQueryString()->through(fn ($p) => $this->resource($p)), 'season' => $s, 'filters' => ['search' => (string) $search, 'position' => $position, 'status' => $status], 'media' => Media::where('status', 'active')->latest()->limit(100)->get()->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'url' => $m->url]), 'award' => $s ? $this->awardResource(PlayerAward::with('player')->where('season_id', $s->id)->where('type', 'player_of_month')->latest('period_month')->first()) : null, 'can' => ['create' => $r->user()->hasPermission('players.create'), 'update' => $r->user()->hasPermission('players.update'), 'stats' => $r->user()->hasPermission('players.stats'), 'awards' => $r->user()->hasPermission('players.awards'), 'delete' => $r->user()->hasPermission('players.delete'), 'browse_media' => $r->user()->hasPermission('media.view'), 'upload_media' => $r->user()->hasPermission('media.upload')]]);
    }

    public function store(Request $r, PlayerService $service): RedirectResponse
    {
        $d = $this->validated($r);
        $this->guardFeatured($d);
        DB::transaction(function () use ($r, $d) {
            if ($d['is_captain']) {
                Player::query()->update(['is_captain' => false]);
            }
            $p = Player::create([...$this->profile($d), 'uuid' => (string) Str::uuid(), 'slug' => $this->uniqueSlug($d['display_name']), 'name' => $d['display_name'], 'number' => $d['shirt_number'], 'position' => $d['primary_position'], 'is_captain' => $d['is_captain'], 'is_featured' => $d['is_featured'], 'featured_order' => $d['featured_order'], 'is_active' => $d['status'] === 'active', 'created_by' => $r->user()->id, 'updated_by' => $r->user()->id]);
            $this->syncMedia($p, $d['primary_media_id'] ?? null);
            PlayerAudit::create(['player_id' => $p->id, 'user_id' => $r->user()->id, 'action' => 'created', 'after_payload' => $d]);
        });
        $service->forget();

        return back()->with('success', 'Pemain berhasil ditambahkan.');
    }

    public function update(Request $r, Player $player, PlayerService $service): RedirectResponse
    {
        $d = $this->validated($r, $player);
        $this->guardFeatured($d, $player);
        $before = $player->toArray();
        DB::transaction(function () use ($r, $player, $d, $before) {
            if ($d['is_captain']) {
                Player::whereKeyNot($player->id)->update(['is_captain' => false]);
            }
            $player->update([...$this->profile($d), 'slug' => $player->slug ?: $this->uniqueSlug($d['display_name'], $player), 'name' => $d['display_name'], 'number' => $d['shirt_number'], 'position' => $d['primary_position'], 'is_captain' => $d['is_captain'], 'is_featured' => $d['is_featured'], 'featured_order' => $d['featured_order'], 'is_active' => $d['status'] === 'active', 'updated_by' => $r->user()->id]);
            $this->syncMedia($player, $d['primary_media_id'] ?? null);
            PlayerAudit::create(['player_id' => $player->id, 'user_id' => $r->user()->id, 'action' => 'updated', 'before_payload' => $before, 'after_payload' => $d]);
        });
        $service->forget();

        return back()->with('success', 'Data pemain berhasil diperbarui.');
    }

    public function destroy(Request $r, Player $player, PlayerService $service): RedirectResponse
    {
        if ($player->stats()->exists() || $player->awards()->exists()) {
            return back()->withErrors(['player' => 'Pemain memiliki riwayat. Ubah status menjadi alumni.']);
        }$player->delete();
        PlayerAudit::create(['player_id' => $player->id, 'user_id' => $r->user()->id, 'action' => 'deleted']);
        $service->forget();

        return back()->with('success', 'Pemain dipindahkan ke arsip.');
    }

    public function saveAward(Request $r, PlayerService $service): RedirectResponse
    {
        $d = $r->validate(['player_id' => ['required', 'exists:players,id'], 'period_month' => ['required', 'date_format:Y-m'], 'title' => ['required', 'string', 'max:120'], 'editorial' => ['nullable', 'string', 'max:1000'], 'appearances' => ['required', 'integer', 'min:0'], 'goals' => ['required', 'integer', 'min:0'], 'assists' => ['required', 'integer', 'min:0'], 'rating' => ['nullable', 'numeric', 'between:0,10'], 'is_published' => ['boolean']]);
        $s = $this->activeSeason();
        PlayerAward::updateOrCreate(['type' => 'player_of_month', 'period_month' => $d['period_month']], [...$d, 'award_type_id' => AwardType::where('slug', 'player-of-the-month')->value('id'), 'season_id' => $s->id, 'period_key' => $d['period_month'], 'statistics_snapshot' => collect($d)->only(['appearances', 'goals', 'assists', 'rating'])->all(), 'status' => $d['is_published'] ? 'published' : 'draft', 'is_featured' => $d['is_published'], 'published_at' => $d['is_published'] ? now() : null]);
        $service->forget();

        return back()->with('success', 'Player of the Month berhasil disimpan.');
    }

    private function validated(Request $r, ?Player $p = null): array
    {
        return $r->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'display_name' => ['required', 'string', 'max:80'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'height_cm' => ['nullable', 'integer', 'between:100,230'],
            'dominant_foot' => ['nullable', Rule::in(['Kanan', 'Kiri', 'Keduanya'])],
            'hometown' => ['nullable', 'string', 'max:100'],
            'biography' => ['nullable', 'string', 'max:1200'],
            'primary_media_id' => ['nullable', 'exists:media,id'],
            'image_focal_x' => ['integer', 'between:0,100'],
            'image_focal_y' => ['integer', 'between:0,100'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'alumni'])],
            'shirt_number' => ['required', 'integer', 'between:1,99', Rule::unique('players', 'number')->ignore($p?->id)],
            'primary_position' => ['required', Rule::in(['Goalkeeper', 'Defender', 'Midfielder', 'Forward'])],
            'is_captain' => ['boolean'],
            'is_featured' => ['boolean'],
            'featured_order' => ['nullable', 'integer', 'between:1,3'],
        ], [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'integer' => ':attribute harus berupa angka bulat.',
            'numeric' => ':attribute harus berupa angka.',
            'date' => 'Format :attribute tidak valid.',
            'before' => ':attribute harus sebelum hari ini.',
            'max.string' => ':attribute maksimal :max karakter.',
            'min.numeric' => ':attribute minimal :min.',
            'between.numeric' => ':attribute harus berada antara :min sampai :max.',
            'in' => 'Pilihan :attribute tidak valid.',
            'exists' => ':attribute yang dipilih tidak tersedia.',
            'boolean' => ':attribute harus berupa pilihan aktif atau tidak aktif.',
            'shirt_number.unique' => 'Nomor punggung sudah digunakan pemain lain.',
        ], [
            'full_name' => 'Nama lengkap',
            'display_name' => 'Nama tampilan',
            'birth_date' => 'Tanggal lahir',
            'height_cm' => 'Tinggi badan',
            'dominant_foot' => 'Kaki dominan',
            'hometown' => 'Kota asal',
            'biography' => 'Biografi',
            'primary_media_id' => 'Foto pemain',
            'image_focal_x' => 'Fokus foto horizontal',
            'image_focal_y' => 'Fokus foto vertikal',
            'status' => 'Status profil',
            'shirt_number' => 'Nomor punggung',
            'primary_position' => 'Posisi utama',
            'is_captain' => 'Pilihan kapten',
            'is_featured' => 'Pilihan pemain unggulan',
            'featured_order' => 'Urutan pemain unggulan',
            'appearances' => 'Jumlah penampilan',
            'starts' => 'Jumlah starter',
            'minutes' => 'Total menit bermain',
            'goals' => 'Jumlah gol',
            'assists' => 'Jumlah assist',
            'clean_sheets' => 'Clean sheet',
            'yellow_cards' => 'Kartu kuning',
            'red_cards' => 'Kartu merah',
            'rating' => 'Rating',
            'attendance_percent' => 'Persentase kehadiran',
        ]);
    }

    private function activeSeason(): Season
    {
        $season = Season::where('is_active', true)->first();

        if (! $season) {
            throw ValidationException::withMessages([
                'player' => 'Belum ada musim aktif. Aktifkan atau tambahkan musim terlebih dahulu sebelum mengelola pemain.',
            ]);
        }

        return $season;
    }

    private function profile(array $d): array
    {
        return collect($d)->only(['full_name', 'display_name', 'birth_date', 'height_cm', 'dominant_foot', 'hometown', 'biography', 'primary_media_id', 'image_focal_x', 'image_focal_y', 'status'])->all();
    }

    private function uniqueSlug(string $name, ?Player $ignore = null): string
    {
        $base = Str::slug($name) ?: 'pemain';
        $slug = $base;
        $suffix = 2;

        while (Player::where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function guardFeatured(array $d, ?Player $p = null): void
    {
        if (($d['is_featured'] ?? false) && Player::where('is_featured', true)->when($p, fn ($q) => $q->whereKeyNot($p->id))->count() >= 3) {
            throw ValidationException::withMessages(['is_featured' => 'Maksimal tiga pemain featured.']);
        }
    }

    private function syncMedia(Player $player, ?int $mediaId): void
    {
        MediaUsage::where('usable_type', $player->getMorphClass())->where('usable_id', $player->id)->where('field_key', 'primary_media_id')->delete();
        if ($mediaId) {
            MediaUsage::create(['media_id' => $mediaId, 'usable_type' => $player->getMorphClass(), 'usable_id' => $player->id, 'field_key' => 'primary_media_id']);
        }
    }

    private function resource(Player $p): array
    {
        $st = $p->stats->first();

        return [...$p->only(['id', 'slug', 'full_name', 'display_name', 'height_cm', 'dominant_foot', 'hometown', 'biography', 'primary_media_id', 'image_focal_x', 'image_focal_y', 'status', 'is_captain', 'is_featured', 'featured_order']), 'birth_date' => $p->birth_date?->format('Y-m-d'), 'photo_url' => $p->photo?->url, 'shirt_number' => $p->number, 'primary_position' => $p->position, 'appearances' => $st?->appearances ?? 0, 'starts' => $st?->starts ?? 0, 'minutes' => $st?->minutes ?? 0, 'goals' => $st?->goals ?? 0, 'assists' => $st?->assists ?? 0, 'clean_sheets' => $st?->clean_sheets ?? 0, 'yellow_cards' => $st?->yellow_cards ?? 0, 'red_cards' => $st?->red_cards ?? 0, 'rating' => $st?->rating, 'attendance_percent' => $st?->attendance_percent];
    }

    private function awardResource(?PlayerAward $a): ?array
    {
        return $a ? [...$a->toArray(), 'player_name' => $a->player?->display_name] : null;
    }
}
