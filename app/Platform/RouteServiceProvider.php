<?php

declare(strict_types=1);

namespace App\Platform;

use App\Models\GameHash;
use App\Models\System;
use App\Platform\Controllers\AchievementController;
use App\Platform\Controllers\Api\GameApiController;
use App\Platform\Controllers\Api\HubApiController;
use App\Platform\Controllers\Api\SystemApiController;
use App\Platform\Controllers\Api\TriggerTicketApiController;
use App\Platform\Controllers\EventController;
use App\Platform\Controllers\EventAwardEarnersController;
use App\Platform\Controllers\GameController;
use App\Platform\Controllers\GameHashController;
use App\Platform\Controllers\GameTopAchieversController;
use App\Platform\Controllers\HubController;
use App\Platform\Controllers\PlayerAchievementController;
use App\Platform\Controllers\PlayerGameController;
use App\Platform\Controllers\ReportAchievementIssueController;
use App\Platform\Controllers\SystemController;
use App\Platform\Controllers\TriggerTicketController;
use App\Platform\Controllers\UserGameAchievementSetPreferenceController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * sanitize route model binding patterns
         */
        Route::pattern('achievement', '[0-9]{1,17}');
        Route::pattern('event', '[a-zA-Z0-9-]+'); // self-healing URLs
        Route::pattern('game', '[0-9]{1,17}');
        Route::pattern('system', '[a-zA-Z0-9-]+'); // self-healing URLs
        Route::pattern('systemId', '[0-9]{1,17}');

        /*
         * Don't reference hash identifiers by their raw ID
         */
        Route::bind('gameHash', function ($value) {
            return GameHash::where('md5', $value)->firstOrFail();
        });
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'csp'])->group(function () {
            Route::group(['prefix' => 'internal-api'], function () {
                Route::get('hub/{gameSet}/games', [HubApiController::class, 'games'])->name('api.hub.game.index');
                Route::get('hub/{gameSet}/games/random', [HubApiController::class, 'randomGame'])->name('api.hub.game.random');

                Route::get('games', [GameApiController::class, 'index'])->name('api.game.index');
                Route::get('games/random', [GameApiController::class, 'random'])->name('api.game.random');

                Route::get('system/{systemId}/games', [SystemApiController::class, 'games'])->name('api.system.game.index');
                Route::get('system/{systemId}/games/random', [SystemApiController::class, 'random'])->name('api.system.game.random');
            });

            Route::middleware(['web', 'inertia'])->group(function () {
                Route::get('event/{event}', [EventController::class, 'show'])->name('event.show');
                Route::get('event/{event}/award-earners', [EventAwardEarnersController::class, 'index'])->name('event.award-earners.index');

                Route::get('game2/{game}', [GameController::class, 'show'])->name('game2.show');
                Route::get('game/{game}/dev-interest', [GameController::class, 'devInterest'])->name('game.dev-interest');
                Route::get('game/{game}/hashes', [GameHashController::class, 'index'])->name('game.hashes.index');
                Route::get('game/{game}/top-achievers', [GameTopAchieversController::class, 'index'])->name('game.top-achievers.index');
                Route::get('game/{game}/suggestions', [GameController::class, 'suggestSimilar'])->name('game.suggestions.similar');

                Route::get('games', [GameController::class, 'index'])->name('game.index');
                Route::get('games/suggestions', [GameController::class, 'suggestPersonalized'])->name('game.suggestions.personalized');

                Route::get('hub/{gameSet}', [HubController::class, 'show'])->name('hub.show');
                Route::get('hubs', [HubController::class, 'show'])->name('hub.index');

                Route::get('system/{system}/games', [SystemController::class, 'games'])->name('system.game.index');

                Route::get('user/{user}/game/{game}/activity', [PlayerGameController::class, 'activity'])->name('user.game.activity.show');
            });

            // Route::get('achievement/{achievement}{slug?}', [AchievementController::class, 'show'])->name('achievement.show');
            // Route::resource('achievements', AchievementController::class)->only('index')->names(['index' => 'achievement.index']);
            // Route::get(
            //     'achievement/{achievement}/players',
            //     [AchievementPlayerController::class, 'index']
            // )->name('achievement.player.index');

            // Route::get('system/{system}{slug?}', [SystemController::class, 'show'])->name('system.show');
            // Route::resource('systems', SystemController::class)->only('index')->names(['index' => 'system.index']);
            /*
             * Note: not allowing to filter achievements on the system level for now
             * stick to games for now
             */
            // Route::get('system/{system}{slug?}/achievements', [SystemController::class, 'achievements'])
            //     ->name('system.achievement.index');

            // Route::get('game/{game}{slug?}', [GameController::class, 'show'])->name('game.show');
            // Route::get('game/{game}/badges', [GameBadgeController::class, 'index'])->name('game.badge.index');
            // Route::get('game/{game}/assets', [GameAssetsController::class, 'index'])->name('game.asset.index');
            // Route::get('game/{game}/players', [GamePlayerController::class, 'index'])->name('game.player.index');
            Route::get('game/random', [GameController::class, 'random'])->name('game.random');

            // Route::get('create', CreateController::class)->name('create');
            // Route::resource('developers', DeveloperController::class)->only('index');

            // Route::resource('game-hashes', GameHashController::class)->only('index')->names(['index' => 'game-hash.index']);

            // Route::resource('leaderboards', LeaderboardController::class)->only('index')->names(['index' => 'leaderboard.index']);
            // Route::resource('leaderboard', LeaderboardController::class)->only('show');

            // Route::get('user/{user}/history', [PlayerHistoryController::class, 'show'])->name('user.history');

            // Route::resource('user.achievements', PlayerAchievementController::class)->only('index')->names(['index' => 'user.achievement.index']);
            // Route::resource('user.games', PlayerGameController::class)->only('index')->names(['index' => 'user.game.index']);
            // Route::resource('user.game', PlayerGameController::class)->only('show');

            // Route::resource('user.badges', PlayerBadgeController::class)->only('index')->names(['index' => 'user.badge.index']);
            // Route::resource('user.badge', PlayerBadgeController::class)->only('show');

            // Route::get('tools', [ToolController::class, 'index'])->name('tool.index');
            // Route::get('tool/hash-check', [ToolController::class, 'hashCheck'])->name('tool.hash-check');

            Route::group([
                'middleware' => ['auth'], // TODO: 'verified'
            ], function () {
                Route::group([
                    'prefix' => 'internal-api',
                ], function () {
                    Route::post('game/{game}/topic', [GameApiController::class, 'generateOfficialForumTopic'])->name('api.game.forum-topic.create');

                    Route::delete('user/game/{game}', [PlayerGameController::class, 'destroy'])->name('api.user.game.destroy');
                    Route::delete('user/achievement/{achievement}', [PlayerAchievementController::class, 'destroy'])->name('api.user.achievement.destroy');

                    Route::put('user/game-achievement-set/{gameAchievementSet}/preference', [UserGameAchievementSetPreferenceController::class, 'update'])
                        ->name('api.user.game-achievement-set.preference.update');

                    Route::post('ticket', [TriggerTicketApiController::class, 'store'])->name('api.ticket.store');
                });

                Route::get('games/resettable', [PlayerGameController::class, 'resettableGames'])->name('player.games.resettable');
                Route::get('game/{game}/achievements/resettable', [PlayerGameController::class, 'resettableGameAchievements'])->name('player.game.achievements.resettable');

                Route::middleware(['inertia'])->group(function () {
                    Route::get('achievement/{achievement}/report-issue', [ReportAchievementIssueController::class, 'index'])->name('achievement.report-issue.index');
                    Route::get('achievement/{achievement}/tickets/create', [TriggerTicketController::class, 'create'])->name('achievement.tickets.create');
                });
            });
        });
    }
}
