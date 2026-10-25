<?php

$text = fn (string $label, string $type = 'text', int $max = 255) => compact('label', 'type', 'max');
$json = fn (string $label, string $help) => ['label' => $label, 'type' => 'json', 'help' => $help];
$image = fn (string $label) => ['label' => $label, 'type' => 'image'];
$hero = fn () => ['pageLabel' => $text('Label Halaman'), 'title' => $text('Judul'), 'titleAccent' => $text('Judul Aksen'), 'description' => $text('Deskripsi', 'textarea', 1200), 'backgroundImage' => $image('Background')];

return [
    'home.hero' => ['fields' => [
        'eyebrow' => $text('Eyebrow'), 'titleTop' => $text('Judul Baris 1'), 'titleBottom' => $text('Judul Baris 2'),
        'description' => $text('Deskripsi', 'textarea', 1200), 'primaryLabel' => $text('Teks Tombol Utama'),
        'primaryHref' => $text('Link Tombol Utama', 'url'), 'backgroundImage' => $image('Background Hero'),
        'established' => $text('Teks Tahun Berdiri'),
        'storyVideoEnabled' => ['label' => 'Tampilkan Video Our Story', 'type' => 'boolean', 'default' => false, 'help' => 'Tampilkan tombol video pada Hero Beranda.'],
        'storyLabel' => ['label' => 'Teks Tombol Video', 'type' => 'text', 'max' => 80, 'showWhen' => ['storyVideoEnabled' => true], 'help' => 'Contoh: Play Video — Our Story.'],
        'storyTitle' => ['label' => 'Judul Video', 'type' => 'text', 'max' => 150, 'showWhen' => ['storyVideoEnabled' => true], 'help' => 'Digunakan sebagai judul modal dan label aksesibilitas.'],
        'storySource' => ['label' => 'Sumber Video', 'type' => 'select', 'options' => ['youtube' => 'YouTube', 'media' => 'Media Library', 'external' => 'Tautan Eksternal'], 'default' => 'youtube', 'showWhen' => ['storyVideoEnabled' => true]],
        'storyUrl' => ['label' => 'URL Video', 'type' => 'url', 'max' => 500, 'showWhenAny' => [['storySource' => 'youtube'], ['storySource' => 'external']], 'help' => 'Gunakan URL YouTube lengkap atau tautan video eksternal.'],
        'storyMediaUrl' => ['label' => 'File Video', 'type' => 'video', 'max' => 500, 'showWhen' => ['storySource' => 'media'], 'help' => 'Pilih video MP4 atau WebM dari Media Library.'],
        'storyThumbnail' => ['label' => 'Thumbnail Video', 'type' => 'image', 'showWhen' => ['storyVideoEnabled' => true], 'help' => 'Opsional. Digunakan sebagai gambar pada tombol video.'],
    ]],
    'home.next_match' => ['fields' => ['label' => $text('Label'), 'homeTeam' => $text('Tim Kandang'), 'homeLogo' => $image('Logo Tim Kandang'), 'awayTeam' => $text('Tim Tandang'), 'awayLogo' => $image('Logo Tim Tandang'), 'awayCode' => $text('Kode Tim Tandang'), 'date' => $text('Tanggal'), 'time' => $text('Waktu'), 'venue' => $text('Lokasi'), 'city' => $text('Kota'), 'detailHref' => $text('Link Detail', 'url'), 'backgroundImage' => $image('Background')]],
    'home.match_center' => ['fields' => ['title' => $text('Judul'), 'allMatchesHref' => $text('Link Semua Pertandingan', 'url'), 'image' => $image('Gambar'), 'upcoming' => $json('Pertandingan Mendatang', 'Array JSON pertandingan.'), 'results' => $json('Hasil Terakhir', 'Array JSON hasil pertandingan.')]],
    'home.squad' => ['fields' => ['title' => $text('Judul'), 'allPlayersHref' => $text('Link Semua Pemain', 'url'), 'players' => $json('Pemain Sorotan', 'Array JSON pemain.')]],
    'home.club_culture' => ['fields' => ['tag' => $text('Label'), 'titleTop' => $text('Judul Baris 1'), 'titleBottom' => $text('Judul Baris 2'), 'description' => $text('Deskripsi', 'textarea', 1200), 'featureImage' => $image('Gambar Utama'), 'featureNote' => $text('Catatan Gambar', 'textarea', 300), 'ctaLabel' => $text('Teks Tombol'), 'ctaHref' => $text('Link Tombol', 'url'), 'gallery' => $json('Galeri', 'Array URL gambar galeri.')]],
    'home.about' => ['fields' => ['kicker' => $text('Kicker'), 'intro' => $text('Intro', 'textarea', 1500), 'storyImage' => $image('Gambar Cerita'), 'city' => $text('Kota'), 'storyTitle' => $text('Judul Cerita'), 'storyAccent' => $text('Judul Aksen'), 'story' => $text('Isi Cerita', 'textarea', 2500), 'identities' => $json('Identitas Klub', 'Array label dan value.'), 'people' => $json('People Behind The Club', 'Array profil pengurus.'), 'partners' => $json('Official Partners', 'Array sponsor.')]],
    'schedule.hero' => ['fields' => $hero() + ['panelLabel' => $text('Label Panel'), 'panelValue' => $text('Nilai Panel'), 'panelStatus' => $text('Status Panel')]],
    'schedule.next_match' => ['fields' => ['match' => $json('Pertandingan Utama', 'Object JSON pertandingan berikutnya.')]],
    'schedule.matches' => ['fields' => ['groups' => $json('Jadwal Per Bulan', 'Array grup bulan dan pertandingan.')]],
    'schedule.results' => ['fields' => ['featured' => $json('Hasil Sorotan', 'Object JSON hasil sorotan.'), 'results' => $json('Daftar Hasil', 'Array JSON hasil.')]],
    'schedule.standings' => ['fields' => ['clubs' => $json('Klasemen', 'Array JSON klub dan statistik.'), 'fullStandingsHref' => $text('Link Klasemen Lengkap', 'url'), 'backgroundImage' => $image('Background')]],
    'league.hero' => ['fields' => $hero()],
    'league.overview' => ['fields' => ['stats' => $json('Statistik Musim', 'Array JSON statistik.'), 'topScorer' => $json('Top Scorer', 'Object JSON pemain.')]],
    'league.standings' => ['fields' => ['standings' => $json('Klasemen Lengkap', 'Array JSON klasemen.')]],
    'league.performance' => ['fields' => ['performance' => $json('Performa COSMIC', 'Object JSON performa.'), 'image' => $image('Gambar'), 'logo' => $image('Logo')]],
    'league.top_players' => ['fields' => ['players' => $json('Top Players', 'Array JSON pemain.')]],
    'league.matchday' => ['fields' => ['results' => $json('Hasil Matchday', 'Array JSON hasil.'), 'fixtures' => $json('Jadwal Berikutnya', 'Array JSON jadwal.')]],
    'league.clubs' => ['fields' => ['clubs' => $json('Klub Peserta', 'Array JSON klub.')]],
    'league.rules' => ['fields' => ['rules' => $json('Regulasi', 'Array JSON judul dan deskripsi.')]],
    'team.hero' => ['fields' => $hero()],
    'team.directory' => ['fields' => ['players' => $json('Daftar Pemain', 'Array JSON pemain.')]],
    'team.player_month' => ['fields' => ['player' => $json('Player of the Month', 'Object JSON pemain.')]],
    'team.captain' => ['fields' => ['captain' => $json('Kapten', 'Object JSON profil kapten.')]],
    'team.staff' => ['fields' => ['staff' => $json('Coaching Staff', 'Array JSON staf.'), 'support' => $json('Support Staff', 'Array JSON staf pendukung.'), 'image' => $image('Gambar Default')]],
    'team.statistics' => ['fields' => ['stats' => $json('Statistik Skuad', 'Array JSON statistik.'), 'impact' => ['label' => 'Squad Impact', 'type' => 'number', 'min' => 0, 'max' => 100], 'image' => $image('Gambar')]],
    'team.composition' => ['fields' => ['positions' => $json('Komposisi Posisi', 'Array JSON posisi.'), 'image' => $image('Gambar Lapangan')]],
];
