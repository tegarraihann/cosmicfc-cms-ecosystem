<?php

$text = fn (string $label, string $type = 'text', int $max = 255) => compact('label', 'type', 'max');
$image = fn (string $label) => ['label' => $label, 'type' => 'image'];
$gallery = fn (string $label, string $help) => ['label' => $label, 'type' => 'gallery', 'help' => $help, 'max' => 6, 'default' => []];
$source = fn (string $label, string $description, string $route) => compact('label', 'description', 'route');
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
    'home.match_center' => ['fields' => ['title' => $text('Judul'), 'allMatchesHref' => $text('Link Semua Pertandingan', 'url'), 'image' => $image('Gambar')], 'sources' => [$source('Pertandingan mendatang dan hasil terakhir', 'Diambil otomatis dari pertandingan yang dipublikasikan.', 'admin.matches.index')]],
    'home.squad' => ['fields' => ['title' => $text('Judul'), 'allPlayersHref' => $text('Link Semua Pemain', 'url')], 'sources' => [$source('Pemain sorotan', 'Diambil otomatis dari pemain aktif yang ditandai sebagai pemain unggulan.', 'admin.players.index')]],
    'home.club_culture' => ['fields' => ['tag' => $text('Label'), 'titleTop' => $text('Judul Baris 1'), 'titleBottom' => $text('Judul Baris 2'), 'description' => $text('Deskripsi', 'textarea', 1200), 'featureImage' => $image('Gambar Utama'), 'featureNote' => $text('Catatan Gambar', 'textarea', 300), 'ctaLabel' => $text('Teks Tombol'), 'ctaHref' => $text('Link Tombol', 'url'), 'gallery' => $gallery('Galeri Budaya Klub', 'Pilih maksimal 6 gambar dari Media Library atau unggah gambar baru.')]],
    'home.about' => ['fields' => ['kicker' => $text('Kicker'), 'intro' => $text('Intro', 'textarea', 1500), 'storyImage' => $image('Gambar Cerita'), 'city' => $text('Kota'), 'storyTitle' => $text('Judul Cerita'), 'storyAccent' => $text('Judul Aksen'), 'story' => $text('Isi Cerita', 'textarea', 2500)], 'sources' => [$source('Identitas klub', 'Tanggal berdiri, founder, dan home base berasal dari Profil Klub.', 'admin.club-profile.edit'), $source('People Behind The Club', 'Diambil otomatis dari Staff & Officials yang aktif.', 'admin.staff.index'), $source('Official Partners', 'Diambil otomatis dari kerja sama sponsor yang aktif.', 'admin.sponsors.index')]],
    'schedule.hero' => ['fields' => $hero() + ['panelLabel' => $text('Label Panel'), 'panelValue' => $text('Nilai Panel'), 'panelStatus' => $text('Status Panel')]],
    'schedule.next_match' => ['fields' => [], 'sources' => [$source('Pertandingan berikutnya', 'Diambil otomatis dari pertandingan terjadwal terdekat yang dipublikasikan.', 'admin.matches.index')]],
    'schedule.matches' => ['fields' => [], 'sources' => [$source('Jadwal pertandingan', 'Dikelompokkan otomatis berdasarkan bulan dari Kelola Pertandingan.', 'admin.matches.index')]],
    'schedule.results' => ['fields' => [], 'sources' => [$source('Hasil pertandingan', 'Diambil otomatis dari pertandingan yang telah selesai dan dipublikasikan.', 'admin.matches.index')]],
    'schedule.standings' => ['fields' => ['fullStandingsHref' => $text('Link Klasemen Lengkap', 'url'), 'backgroundImage' => $image('Background')], 'sources' => [$source('Klasemen ringkas', 'Diambil otomatis dari klasemen musim aktif.', 'admin.standings.index')]],
    'league.hero' => ['fields' => $hero()],
    'league.overview' => ['fields' => [], 'sources' => [$source('Statistik musim', 'Diambil otomatis dari kompetisi dan musim aktif.', 'admin.competitions.index'), $source('Top scorer', 'Dihitung otomatis dari statistik pemain.', 'admin.players.index')]],
    'league.standings' => ['fields' => [], 'sources' => [$source('Klasemen lengkap', 'Diambil otomatis dari klasemen musim aktif.', 'admin.standings.index')]],
    'league.performance' => ['fields' => ['image' => $image('Gambar'), 'logo' => $image('Logo')], 'sources' => [$source('Performa COSMIC', 'Dihitung otomatis dari klasemen dan hasil pertandingan.', 'admin.standings.index')]],
    'league.top_players' => ['fields' => [], 'sources' => [$source('Top players', 'Dihitung otomatis dari statistik pemain.', 'admin.players.index')]],
    'league.matchday' => ['fields' => [], 'sources' => [$source('Hasil dan jadwal matchday', 'Diambil otomatis dari Kelola Pertandingan.', 'admin.matches.index')]],
    'league.clubs' => ['fields' => [], 'sources' => [$source('Klub peserta', 'Diambil otomatis dari peserta musim aktif.', 'admin.competitions.index')]],
    'league.rules' => ['fields' => [], 'sources' => [$source('Regulasi kompetisi', 'Diambil otomatis dari pengaturan musim aktif.', 'admin.competitions.index')]],
    'team.hero' => ['fields' => $hero()],
    'team.directory' => ['fields' => [], 'sources' => [$source('Daftar pemain', 'Diambil otomatis dari pemain aktif.', 'admin.players.index')]],
    'team.player_month' => ['fields' => [], 'sources' => [$source('Player of the Month', 'Diambil otomatis dari penghargaan pemain yang dipublikasikan.', 'admin.player-awards.index')]],
    'team.captain' => ['fields' => [], 'sources' => [$source('Kapten tim', 'Diambil otomatis dari pemain yang ditetapkan sebagai kapten.', 'admin.players.index')]],
    'team.staff' => ['fields' => ['image' => $image('Gambar Default')], 'sources' => [$source('Coaching dan support staff', 'Diambil otomatis dari Staff & Officials yang aktif.', 'admin.staff.index')]],
    'team.statistics' => ['fields' => ['impact' => ['label' => 'Squad Impact', 'type' => 'number', 'min' => 0, 'max' => 100], 'image' => $image('Gambar')], 'sources' => [$source('Statistik skuad', 'Dihitung otomatis dari statistik pemain.', 'admin.players.index')]],
    'team.composition' => ['fields' => ['image' => $image('Gambar Lapangan')], 'sources' => [$source('Komposisi posisi', 'Dihitung otomatis dari posisi pemain aktif.', 'admin.players.index')]],
];
