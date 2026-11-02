<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\GameMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class November2026ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $cosmic = $this->club(
            slug: 'cosmic',
            officialName: 'COSMIC',
            displayName: 'COSMIC',
            shortName: 'CSM',
            type: 'internal',
            isCosmic: true,
        );

        $fixtures = [
            [
                'slug' => 'cosmic-vs-medima-fc-2026-11-03',
                'opponent' => ['medima-fc', 'MEDIMA FC', 'MEDIMA FC', 'MFC'],
                'kickoff_at' => '2026-11-03 20:00:00',
                'venue_name' => 'Astaka Arena',
                'status' => 'scheduled',
                'is_public' => true,
            ],
            [
                'slug' => 'cosmic-vs-lo-fc-2026-11-05',
                'opponent' => ['lo-fc', 'L.O FC', 'L.O FC', 'LOFC'],
                'kickoff_at' => '2026-11-05 21:00:00',
                'venue_name' => 'Take Off',
                'status' => 'scheduled',
                'is_public' => true,
            ],
            [
                'slug' => 'cosmic-vs-gaspol-fc-2026-11-07',
                'opponent' => ['gaspol-fc', 'GASPOL FC', 'GASPOL FC', 'GFC'],
                'kickoff_at' => '2026-11-07 21:00:00',
                'venue_name' => 'Astaka Arena',
                'status' => 'scheduled',
                'is_public' => true,
            ],
            [
                'slug' => 'cosmic-vs-al-khaf-fc-2026-11-10',
                'opponent' => ['al-khaf-fc', 'AL KHAF FC', 'AL KHAF FC', 'AKFC'],
                'kickoff_at' => null,
                'venue_name' => null,
                'status' => 'draft',
                'is_public' => false,
                'short_report' => 'Rencana pertandingan 10 November 2026. Waktu kick-off dan lapangan belum ditentukan.',
            ],
        ];

        foreach ($fixtures as $fixture) {
            [$clubSlug, $officialName, $displayName, $shortName] = $fixture['opponent'];
            $opponent = $this->club($clubSlug, $officialName, $displayName, $shortName);

            $match = GameMatch::withTrashed()->firstOrNew(['slug' => $fixture['slug']]);

            if (! $match->exists) {
                $match->uuid = (string) Str::uuid();
            }

            $match->fill([
                'season_id' => null,
                'match_type' => 'sparing',
                'matchday' => null,
                'round_name' => null,
                'home_club_id' => $cosmic->id,
                'away_club_id' => $opponent->id,
                'kickoff_at' => $fixture['kickoff_at'],
                'timezone' => 'Asia/Jakarta',
                'venue_name' => $fixture['venue_name'],
                'venue_address' => null,
                'status' => $fixture['status'],
                'score_mode' => 'automatic',
                'home_score' => null,
                'away_score' => null,
                'home_penalty_score' => null,
                'away_penalty_score' => null,
                'manual_score_reason' => null,
                'count_for_standings' => false,
                'is_featured' => false,
                'is_public' => $fixture['is_public'],
                'short_report' => $fixture['short_report'] ?? null,
                'published_at' => $fixture['is_public'] ? now() : null,
            ]);
            $match->deleted_at = null;
            $match->save();
        }
    }

    private function club(
        string $slug,
        string $officialName,
        string $displayName,
        string $shortName,
        string $type = 'opponent',
        bool $isCosmic = false,
    ): Club {
        $club = Club::withTrashed()->firstOrNew(['slug' => $slug]);

        if (! $club->exists) {
            $club->uuid = (string) Str::uuid();
        }

        $club->fill([
            'official_name' => $officialName,
            'display_name' => $displayName,
            'short_name' => $shortName,
            'type' => $type,
            'country' => 'Indonesia',
            'is_cosmic' => $isCosmic,
            'status' => 'active',
        ]);
        $club->deleted_at = null;
        $club->save();

        return $club;
    }
}
