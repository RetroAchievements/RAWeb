<?php

declare(strict_types=1);

namespace App\Platform;

use App\Platform\Controllers\ApiDocsController;
use App\Platform\Controllers\BeatenGamesLeaderboardController;
use App\Platform\Controllers\GameDevInterestController;
use App\Platform\Controllers\PlayerCompletionProgressController;
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
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'csp'])->group(function () {
            // Route::get('achievement/{achievement}{slug?}', [AchievementController::class, 'show'])->name('achievement.show');
            // Route::resource('achievements', AchievementController::class)->only('index')->names(['index' => 'achievement.index']);
            // Route::get(
            //     'achievement/{achievement}/players',
            //     [AchievementPlayerController::class, 'index']
            // )->name('achievement.player.index');

            // Route::get('system/{system}{slug?}', [SystemController::class, 'show'])->name('system.show');
            // Route::resource('systems', SystemController::class)->only('index')->names(['index' => 'system.index']);
            // Route::get('system/{system}/games', [SystemController::class, 'games'])
            //     ->name('system.game.index');
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
            Route::get('game/{game}/dev-interest', GameDevInterestController::class)->name('game.dev-interest');

            // Route::get('create', CreateController::class)->name('create');
            // Route::resource('developers', DeveloperController::class)->only('index');

            Route::get('docs/api', [ApiDocsController::class, 'index'])->name('docs.api');

            // Route::resource('game-hashes', GameHashController::class)->only('index')->names(['index' => 'game-hash.index']);
            // Route::resource('game-hash', GameHashController::class)->only('show')->names(['show' => 'game-hash.show']);

            Route::get('ranking/beaten-games', BeatenGamesLeaderboardController::class)->name('ranking.beaten-games');

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
                'middleware' => ['auth', 'verified'],
            ], function () {
            //     /*
            //      * Release Management Routes
            //      */
            //     Route::resource('emulators', EmulatorController::class)->only('index')->names(['index' => 'emulator.index']);
            //     Route::resource('emulator', EmulatorController::class)->only(
            //         'create',
            //         'store',
            //         'edit',
            //         'update',
            //         'destroy'
            //     );
            //     Route::resource('emulator.release', EmulatorReleaseController::class)->only('create', 'store');
            //     Route::resource('emulator.releases', EmulatorReleaseController::class)->only('index')->names(['index' => 'emulator.release.index']);
            //     Route::group(['prefix' => 'emulators'], function () {
            //         Route::resource('emulator.release', EmulatorReleaseController::class)->only('edit', 'update', 'destroy')
            //             ->names([
            //                 'edit' => 'emulator.release.edit',
            //                 'update' => 'emulator.release.update',
            //                 'destroy' => 'emulator.release.destroy',
            //             ])
            //             ->shallow();
            //         Route::get('release/{release}/restore', [EmulatorReleaseController::class, 'restore'])
            //             ->name('emulator.release.restore');
            //         // Route::delete('release/{release}/forceDelete', [EmulatorReleaseController::class, 'forceDestroy'])
            //         //     ->name('emulator.release.force-destroy');
            //     });
            //     Route::group(['prefix' => 'integrations'], function () {
            //         Route::resource('releases', IntegrationReleaseController::class)->only('index')->names(['index' => 'integration.release.index']);
            //         Route::resource('release', IntegrationReleaseController::class)
            //             ->shallow()
            //             ->only(
            //                 'create',
            //                 'store',
            //                 'edit',
            //                 'update',
            //                 'destroy'
            //             )
            //             ->names([
            //                 'create' => 'integration.release.create',
            //                 'store' => 'integration.release.store',
            //                 'edit' => 'integration.release.edit',
            //                 'update' => 'integration.release.update',
            //                 'destroy' => 'integration.release.destroy',
            //             ]);
            //         Route::get('release/{release}/restore', [IntegrationReleaseController::class, 'restore'])
            //             ->name('integration.release.restore');
            //         // Route::delete('release/{release}/forceDelete', [IntegrationReleaseController::class, 'forceDestroy'])
            //         //     ->name('integration.release.force-destroy');
            //     });

            //     Route::resource('achievement', AchievementController::class)->only('edit', 'update', 'destroy');
            //     Route::resource('system', SystemController::class)->only('edit', 'update', 'destroy');
            //     Route::resource('game', GameController::class)->only('edit', 'update', 'destroy');
            //
                Route::get('user/{user}/progress', PlayerCompletionProgressController::class)->name('user.completion-progress');

            //     Route::get('user/{user}/game/{game}/compare', [PlayerGameController::class, 'compare'])
            //         ->name('user.game.compare');
            });
        });
    }
}
