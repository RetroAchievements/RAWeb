<?php

declare(strict_types=1);

namespace App\Platform;

use App\Models\GameHash;
use App\Platform\Controllers\AchievementController;
use App\Platform\Controllers\GameController;
use App\Platform\Controllers\GameHashController;
use App\Platform\Controllers\PlayerAchievementController;
use App\Platform\Controllers\PlayerGameController;
use App\Platform\Controllers\ReportAchievementIssueController;
use App\Platform\Controllers\SystemController;
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
        Route::pattern('game', '[0-9]{1,17}');
        Route::pattern('game_hash', '[a-zA-Z0-9]{1,32}');
        Route::pattern('system', '[0-9]{1,17}');

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
            Route::middleware(['inertia'])->group(function () {
                Route::get('game/{game}/hashes', [GameHashController::class, 'index'])->name('game.hashes.index');
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
            // Route::resource('games', GameController::class)->only('index')->names(['index' => 'game.index']);
            // Route::get('games/popular', [GameController::class, 'popular'])->name('games.popular');
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
                Route::resource('game-hash', GameHashController::class)->parameters(['game-hash' => 'gameHash'])->only(['update', 'destroy']);

                Route::get('games/resettable', [PlayerGameController::class, 'resettableGames'])->name('player.games.resettable');
                Route::get('game/{game}/achievements/resettable', [PlayerGameController::class, 'resettableGameAchievements'])->name('player.game.achievements.resettable');

                Route::delete('user/game/{game}', [PlayerGameController::class, 'destroy'])->name('user.game.destroy');
                Route::delete('user/achievement/{achievement}', [PlayerAchievementController::class, 'destroy'])->name('user.achievement.destroy');

                Route::middleware(['inertia'])->group(function () {
                    Route::get('achievement/{achievement}/report-issue', [ReportAchievementIssueController::class, 'index'])->name('achievement.report-issue.index');
                });
            });
        });
    }
}
