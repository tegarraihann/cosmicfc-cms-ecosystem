<?php

use App\Http\Controllers\Admin\AccessManagementController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnalyticsSettingsController;
use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\ClubProfileController;
use App\Http\Controllers\Admin\CompetitionController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\MatchEventController;
use App\Http\Controllers\Admin\MatchMvpController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\PageManagementController;
use App\Http\Controllers\Admin\PlayerAwardController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\SectionContentController;
use App\Http\Controllers\Admin\SponsorController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StandingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPageController;
use App\Services\PageLayoutService;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (PublicPageController $controller) => $controller->show('home', app(PageLayoutService::class)))->name('home');
Route::get('/jadwal', fn (PublicPageController $controller) => $controller->show('schedule', app(PageLayoutService::class)))->name('schedule');
Route::get('/liga', fn (PublicPageController $controller) => $controller->show('league', app(PageLayoutService::class)))->name('league');
Route::get('/tim', fn (PublicPageController $controller) => $controller->show('team', app(PageLayoutService::class)))->name('team');
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::get('/pertandingan/{match}', [PublicPageController::class, 'match'])->name('matches.show');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'admin', 'permission:dashboard.view'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/media', [MediaController::class, 'index'])->middleware('permission:media.view')->name('media.index');
        Route::get('/media/items', [MediaController::class, 'items'])->middleware('permission:media.view')->name('media.items');
        Route::post('/media', [MediaController::class, 'store'])->middleware('permission:media.upload')->name('media.store');
        Route::put('/media/{media}', [MediaController::class, 'update'])->middleware('permission:media.update')->name('media.update');
        Route::delete('/media/{media}', [MediaController::class, 'destroy'])->middleware('permission:media.delete')->name('media.destroy');
        Route::post('/media/{media}/restore', [MediaController::class, 'restore'])->middleware('permission:media.restore')->name('media.restore');
    });

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/pages')->name('admin.pages.')->group(function () {
    Route::get('/{page?}', [PageManagementController::class, 'index'])->middleware('permission:landing.view')->name('index');
    Route::put('/{layout}/draft', [PageManagementController::class, 'update'])->middleware('permission:landing.update')->name('update');
    Route::post('/{layout}/publish', [PageManagementController::class, 'publish'])->middleware('permission:landing.publish')->name('publish');
    Route::post('/{layout}/discard', [PageManagementController::class, 'discard'])->middleware('permission:landing.update')->name('discard');
    Route::get('/{layout}/preview', [PageManagementController::class, 'preview'])->middleware('permission:landing.preview')->name('preview');
});

Route::middleware(['auth', 'verified', 'admin', 'permission:landing.update'])->prefix('admin/sections')->name('admin.sections.')->group(function () {
    Route::get('/{section}/edit', [SectionContentController::class, 'edit'])->name('edit');
    Route::put('/{section}', [SectionContentController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/club-profile')->name('admin.club-profile.')->group(function () {
    Route::get('/', [ClubProfileController::class, 'edit'])->middleware('permission:club-profile.view')->name('edit');
    Route::put('/', [ClubProfileController::class, 'update'])->middleware('permission:club-profile.update')->name('update');
    Route::post('/publish', [ClubProfileController::class, 'publish'])->middleware('permission:club-profile.publish')->name('publish');
    Route::post('/discard', [ClubProfileController::class, 'discard'])->middleware('permission:club-profile.update')->name('discard');
    Route::get('/preview', [ClubProfileController::class, 'preview'])->middleware('permission:club-profile.preview')->name('preview');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/analytics')->name('admin.analytics.')->group(function () {
    Route::get('/', [AnalyticsController::class, 'index'])->middleware('permission:analytics.view')->name('index');
    Route::post('/refresh', [AnalyticsController::class, 'refresh'])->middleware('permission:analytics.refresh')->name('refresh');
    Route::get('/refresh', fn () => redirect()->route('admin.analytics.index'))->middleware('permission:analytics.view')->name('refresh.fallback');
    Route::put('/settings', [AnalyticsSettingsController::class, 'update'])->middleware('permission:analytics.configure')->name('settings.update');
    Route::post('/settings/test', [AnalyticsSettingsController::class, 'test'])->middleware(['permission:analytics.configure', 'throttle:3,1'])->name('settings.test');
    Route::delete('/settings/credentials', [AnalyticsSettingsController::class, 'destroyCredentials'])->middleware('permission:analytics.configure')->name('settings.credentials.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/players')->name('admin.players.')->group(function () {
    Route::get('/', [PlayerController::class, 'index'])->middleware('permission:players.view')->name('index');
    Route::post('/', [PlayerController::class, 'store'])->middleware('permission:players.create')->name('store');
    Route::put('/{player}', [PlayerController::class, 'update'])->middleware('permission:players.update')->name('update');
    Route::delete('/{player}', [PlayerController::class, 'destroy'])->middleware('permission:players.delete')->name('destroy');
    Route::post('/awards/player-of-month', [PlayerController::class, 'saveAward'])->middleware('permission:players.awards')->name('awards.save');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/staff')->name('admin.staff.')->group(function () {
    Route::get('/', [StaffController::class, 'index'])->middleware('permission:staff.view')->name('index');
    Route::post('/', [StaffController::class, 'store'])->middleware('permission:staff.create')->name('store');
    Route::put('/{staff}', [StaffController::class, 'update'])->middleware('permission:staff.update')->name('update');
    Route::delete('/{staff}', [StaffController::class, 'destroy'])->middleware('permission:staff.delete')->name('destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/clubs')->name('admin.clubs.')->group(function () {
    Route::get('/', [ClubController::class, 'index'])->middleware('permission:clubs.view')->name('index');
    Route::post('/', [ClubController::class, 'store'])->middleware('permission:clubs.create')->name('store');
    Route::put('/{club}', [ClubController::class, 'update'])->middleware('permission:clubs.update')->name('update');
    Route::post('/{club}/archive', [ClubController::class, 'archive'])->middleware('permission:clubs.archive')->name('archive');
    Route::delete('/{club}', [ClubController::class, 'destroy'])->middleware('permission:clubs.delete')->name('destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/competitions')->name('admin.competitions.')->group(function () {
    Route::get('/', [CompetitionController::class, 'index'])->middleware('permission:competitions.view')->name('index');
    Route::get('/create', [CompetitionController::class, 'create'])->middleware('permission:competitions.create')->name('create');
    Route::get('/seasons/create', [CompetitionController::class, 'createSeason'])->middleware('permission:competitions.update')->name('seasons.create');
    Route::post('/', [CompetitionController::class, 'store'])->middleware('permission:competitions.create')->name('store');
    Route::put('/{competition}', [CompetitionController::class, 'update'])->middleware('permission:competitions.update')->name('update');
    Route::post('/{competition}/seasons/{season?}', [CompetitionController::class, 'saveSeason'])->middleware('permission:competitions.update')->name('seasons.save');
    Route::put('/seasons/{season}/participants', [CompetitionController::class, 'syncParticipants'])->middleware('permission:competitions.participants')->name('participants.sync');
    Route::delete('/{competition}', [CompetitionController::class, 'destroy'])->middleware('permission:competitions.delete')->name('destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/matches')->name('admin.matches.')->group(function () {
    Route::get('/', [MatchController::class, 'index'])->middleware('permission:matches.view')->name('index');
    Route::get('/{match}/mvp', [MatchMvpController::class, 'show'])->middleware('permission:player-awards.view')->name('mvp.show');
    Route::put('/{match}/mvp/assessments', [MatchMvpController::class, 'saveAssessments'])->middleware('permission:player-awards.create')->name('mvp.assessments');
    Route::post('/{match}/mvp/decision', [MatchMvpController::class, 'saveDecision'])->middleware('permission:player-awards.create')->name('mvp.decision');
    Route::post('/', [MatchController::class, 'store'])->middleware('permission:matches.create')->name('store');
    Route::put('/{match}', [MatchController::class, 'update'])->middleware('permission:matches.update')->name('update');
    Route::put('/{match}/lineups', [MatchController::class, 'syncLineups'])->middleware('permission:matches.lineups')->name('lineups.sync');
    Route::post('/{match}/heartbeat', [MatchController::class, 'heartbeat'])->middleware('permission:matches.view')->name('heartbeat');
    Route::post('/{match}/events', [MatchEventController::class, 'store'])->middleware('permission:match-events.create')->name('events.store');
    Route::put('/{match}/events/{event}', [MatchEventController::class, 'update'])->middleware('permission:match-events.update')->name('events.update');
    Route::delete('/{match}/events/{event}', [MatchEventController::class, 'destroy'])->middleware('permission:match-events.delete')->name('events.destroy');
    Route::delete('/{match}', [MatchController::class, 'destroy'])->middleware('permission:matches.delete')->name('destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/standings')->name('admin.standings.')->group(function () {
    Route::get('/', [StandingsController::class, 'index'])->middleware('permission:standings.view')->name('index');
    Route::post('/seasons/{season}/recalculate', [StandingsController::class, 'recalculate'])->middleware('permission:standings.recalculate')->name('recalculate');
    Route::post('/seasons/{season}/adjustments', [StandingsController::class, 'storeAdjustment'])->middleware('permission:standings.adjust')->name('adjustments.store');
    Route::get('/seasons/{season}/export', [StandingsController::class, 'export'])->middleware('permission:standings.export')->name('export');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/player-awards')->name('admin.player-awards.')->group(function () {
    Route::get('/', [PlayerAwardController::class, 'index'])->middleware('permission:player-awards.view')->name('index');
    Route::post('/', [PlayerAwardController::class, 'store'])->middleware('permission:player-awards.create')->name('store');
    Route::put('/{award}', [PlayerAwardController::class, 'update'])->middleware('permission:player-awards.update')->name('update');
    Route::post('/{award}/archive', [PlayerAwardController::class, 'archive'])->middleware('permission:player-awards.archive')->name('archive');
    Route::post('/types', [PlayerAwardController::class, 'saveType'])->middleware('permission:player-awards.types')->name('types.save');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/sponsors')->name('admin.sponsors.')->group(function () {
    Route::get('/', [SponsorController::class, 'index'])->middleware('permission:sponsors.view')->name('index');
    Route::post('/', [SponsorController::class, 'store'])->middleware('permission:sponsors.create')->name('store');
    Route::put('/{sponsor}', [SponsorController::class, 'update'])->middleware('permission:sponsors.update')->name('update');
    Route::post('/{sponsor}/partnerships/{partnership?}', [SponsorController::class, 'savePartnership'])->middleware('permission:sponsors.update')->name('partnerships.save');
    Route::post('/{sponsor}/archive', [SponsorController::class, 'archive'])->middleware('permission:sponsors.archive')->name('archive');
    Route::delete('/{sponsor}', [SponsorController::class, 'destroy'])->middleware('permission:sponsors.delete')->name('destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/news')->name('admin.news.')->group(function () {
    Route::get('/', [AdminNewsController::class, 'index'])->middleware('permission:news.view')->name('index');
    Route::get('/create', [AdminNewsController::class, 'create'])->middleware('permission:news.create')->name('create');
    Route::post('/', [AdminNewsController::class, 'store'])->middleware('permission:news.create')->name('store');
    Route::get('/{article}/edit', [AdminNewsController::class, 'edit'])->middleware('permission:news.update')->name('edit');
    Route::put('/{article}', [AdminNewsController::class, 'update'])->middleware('permission:news.update')->name('update');
    Route::post('/{article}/archive', [AdminNewsController::class, 'archive'])->middleware('permission:news.archive')->name('archive');
    Route::post('/{article}/restore', [AdminNewsController::class, 'restore'])->middleware('permission:news.archive')->name('restore');
    Route::post('/categories', [AdminNewsController::class, 'saveCategory'])->middleware('permission:news.categories')->name('categories.save');
});

Route::get('/club-profile-preview', fn (PublicPageController $controller) => $controller->show('home', app(PageLayoutService::class)))
    ->middleware(['auth', 'verified', 'admin', 'signed', 'permission:club-profile.preview'])->name('preview.profile');

Route::get('/preview/{page}', [PublicPageController::class, 'preview'])
    ->middleware(['auth', 'verified', 'admin', 'signed', 'permission:landing.preview'])->name('preview.page');

Route::middleware(['auth', 'verified', 'superadmin'])->prefix('admin/access')->name('admin.access.')->group(function () {
    Route::get('/', [AccessManagementController::class, 'index'])->name('index');
    Route::post('/users', [AccessManagementController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{user}/resend-invitation', [AccessManagementController::class, 'resendInvitation'])->name('users.invitation.resend');
    Route::put('/users/{user}', [AccessManagementController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AccessManagementController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/roles', [AccessManagementController::class, 'storeRole'])->name('roles.store');
    Route::put('/roles/{role}', [AccessManagementController::class, 'updateRole'])->name('roles.update');
    Route::delete('/roles/{role}', [AccessManagementController::class, 'destroyRole'])->name('roles.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/pending-email/resend', [ProfileController::class, 'resendPendingEmail'])->middleware('throttle:3,1')->name('profile.email.resend');
    Route::delete('/profile/pending-email', [ProfileController::class, 'cancelPendingEmail'])->name('profile.email.cancel');
});

require __DIR__.'/auth.php';
