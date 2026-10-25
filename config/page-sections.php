<?php

return [
    'home' => ['label' => 'Beranda', 'path' => '/', 'component' => 'Public/Home', 'sections' => [
        ['key' => 'home.hero', 'label' => 'Hero Beranda', 'component' => 'HeroSection', 'locked' => true],
        ['key' => 'home.next_match', 'label' => 'Next Match', 'component' => 'NextMatchSection'],
        ['key' => 'home.match_center', 'label' => 'Match Center', 'component' => 'MatchCenterSection'],
        ['key' => 'home.squad', 'label' => 'Meet The Squad', 'component' => 'SquadSection'],
        ['key' => 'home.club_culture', 'label' => 'Club Culture', 'component' => 'ClubCultureSection'],
        ['key' => 'home.about', 'label' => 'Tentang COSMIC', 'component' => 'AboutCosmicSection'],
    ]],
    'schedule' => ['label' => 'Jadwal', 'path' => '/jadwal', 'component' => 'Public/Schedule', 'sections' => [
        ['key' => 'schedule.hero', 'label' => 'Hero Jadwal', 'component' => 'PageHeroSection', 'locked' => true],
        ['key' => 'schedule.next_match', 'label' => 'Next Match', 'component' => 'ScheduleNextMatchSection'],
        ['key' => 'schedule.matches', 'label' => 'Match Schedule', 'component' => 'MatchScheduleSection'],
        ['key' => 'schedule.results', 'label' => 'Hasil Pertandingan', 'component' => 'MatchResultsSection'],
        ['key' => 'schedule.standings', 'label' => 'Klasemen Ringkas', 'component' => 'CompactStandingsSection'],
    ]],
    'league' => ['label' => 'Liga', 'path' => '/liga', 'component' => 'Public/League', 'sections' => [
        ['key' => 'league.hero', 'label' => 'Hero Liga', 'component' => 'PageHeroSection', 'locked' => true],
        ['key' => 'league.overview', 'label' => 'Ringkasan Musim', 'component' => 'SeasonOverviewSection'],
        ['key' => 'league.standings', 'label' => 'Klasemen Lengkap', 'component' => 'FullStandingsSection'],
        ['key' => 'league.performance', 'label' => 'Performa COSMIC', 'component' => 'CosmicPerformanceSection'],
        ['key' => 'league.top_players', 'label' => 'Top Players', 'component' => 'TopPlayersSection'],
        ['key' => 'league.matchday', 'label' => 'Matchday Center', 'component' => 'MatchdayCenterSection'],
        ['key' => 'league.clubs', 'label' => 'Klub Peserta', 'component' => 'ParticipatingClubsSection'],
        ['key' => 'league.rules', 'label' => 'Competition Rules', 'component' => 'CompetitionRulesSection'],
    ]],
    'team' => ['label' => 'Tim', 'path' => '/tim', 'component' => 'Public/Team', 'sections' => [
        ['key' => 'team.hero', 'label' => 'Hero Tim', 'component' => 'PageHeroSection', 'locked' => true],
        ['key' => 'team.directory', 'label' => 'Daftar Pemain', 'component' => 'SquadDirectorySection'],
        ['key' => 'team.player_month', 'label' => 'Player of the Month', 'component' => 'PlayerOfMonthSection'],
        ['key' => 'team.captain', 'label' => 'Captain Spotlight', 'component' => 'CaptainSpotlightSection'],
        ['key' => 'team.staff', 'label' => 'Coaching Staff', 'component' => 'CoachingStaffSection'],
        ['key' => 'team.statistics', 'label' => 'Statistik Skuad', 'component' => 'SquadStatisticsSection'],
        ['key' => 'team.composition', 'label' => 'Komposisi Posisi', 'component' => 'PositionCompositionSection'],
    ]],
];
