<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dynamicKeys = [
            'home.match_center' => ['upcoming', 'results'],
            'home.squad' => ['players'],
            'home.about' => ['identities', 'people', 'partners'],
            'schedule.next_match' => ['match'],
            'schedule.matches' => ['groups'],
            'schedule.results' => ['featured', 'results'],
            'schedule.standings' => ['clubs'],
            'league.overview' => ['stats', 'topScorer'],
            'league.standings' => ['standings'],
            'league.performance' => ['performance'],
            'league.top_players' => ['players'],
            'league.matchday' => ['results', 'fixtures'],
            'league.clubs' => ['clubs'],
            'league.rules' => ['rules'],
            'team.directory' => ['players'],
            'team.player_month' => ['player'],
            'team.captain' => ['captain'],
            'team.staff' => ['staff', 'support'],
            'team.statistics' => ['stats'],
            'team.composition' => ['positions'],
        ];

        DB::table('section_content_versions')
            ->join('page_sections', 'page_sections.id', '=', 'section_content_versions.page_section_id')
            ->whereIn('page_sections.section_key', array_keys($dynamicKeys))
            ->select('section_content_versions.id', 'section_content_versions.content', 'page_sections.section_key')
            ->orderBy('section_content_versions.id')
            ->each(function ($version) use ($dynamicKeys) {
                $content = json_decode($version->content ?: '[]', true) ?: [];

                foreach ($dynamicKeys[$version->section_key] as $key) {
                    unset($content[$key]);
                }

                DB::table('section_content_versions')->where('id', $version->id)->update([
                    'content' => json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
            });
    }

    public function down(): void
    {
        // Data dinamis lama sengaja tidak dikembalikan karena sumber resminya
        // adalah modul pertandingan, pemain, kompetisi, staff, dan sponsor.
    }
};
