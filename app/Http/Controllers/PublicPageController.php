<?php

namespace App\Http\Controllers;

use App\Services\ClubService;
use App\Services\CompetitionService;
use App\Services\MatchService;
use App\Services\PageLayoutService;
use App\Services\PlayerAwardService;
use App\Services\PlayerService;
use App\Services\StaffService;
use App\Services\StandingsService;
use App\Services\SponsorService;
use Inertia\Inertia;
use Inertia\Response;

class PublicPageController extends Controller
{
    public function match(string $match, MatchService $matches): Response
    {
        $data = $matches->detail($match);
        abort_unless($data, 404);

        return Inertia::render('Public/MatchDetail', ['matchKey' => $match, 'matchData' => $data, 'awardData' => app(PlayerAwardService::class)->matchAward($data['id'])]);
    }

    public function show(string $page, PageLayoutService $layouts): Response
    {
        return $this->render($page, $layouts, false);
    }

    public function preview(string $page, PageLayoutService $layouts): Response
    {
        return $this->render($page, $layouts, true);
    }

    private function render(string $page, PageLayoutService $layouts, bool $draft): Response
    {
        $definition = config("page-sections.{$page}");
        abort_unless($definition, 404);

        return Inertia::render($definition['component'], [
            'layoutSections' => $layouts->sections($page, $draft)->values(),
            'isPreview' => $draft,
            'playerData' => app(PlayerService::class)->publicData(),
            'staffData' => app(StaffService::class)->publicData(),
            'clubData' => app(ClubService::class)->publicData(),
            'competitionData' => app(CompetitionService::class)->publicData(),
            'matchData' => app(MatchService::class)->publicData(),
            'standingsData' => app(StandingsService::class)->publicData(),
            'awardData' => app(PlayerAwardService::class)->publicData(),
            'sponsorData' => app(SponsorService::class)->publicData(),
        ]);
    }
}
