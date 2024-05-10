<?php

declare(strict_types=1);

namespace App\Platform;

use App\Community\Controllers\CommentController;
use App\Community\Controllers\TicketController;
use App\Models\GameHash;
use App\Platform\Controllers\AchievementController;
use App\Platform\Controllers\BeatenGamesLeaderboardController;
use App\Platform\Controllers\CompareUnlocksController;
use App\Platform\Controllers\DeveloperFeedController;
use App\Platform\Controllers\DeveloperSetsController;
use App\Platform\Controllers\GameDevInterestController;
use App\Platform\Controllers\GameHashController;
use App\Platform\Controllers\PlayerCompletionProgressController;
use App\Platform\Controllers\PlayerGameController;
use App\Platform\Controllers\SuggestGameController;
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
            // Route::get('achievement/{achievement}{slug?}', [AchievementController::class, 'show'])->name('achievement.show');
            // Route::resource('achievements', AchievementController::class)->only('index')->names(['index' => 'achievement.index']);
            // Route::get(
            //     'achievement/{achievement}/players',
            //     [AchievementPlayerController::class, 'index']
            // )->name('achievement.player.index');

            // Route::get('system/{system}{slug?}', [SystemController::class, 'show'])->name('system.show');
            // Route::resource('systems', SystemController::class)->only('index')->names(['index' => 'system.index']);
            Route::get('system/{system}/games', [SystemController::class, 'games'])
                ->name('system.game.index');
            /*
             * Note: not allowing to filter achievements on the system level for now
             * stick to games for now
             */
            // Route::get('system/{system}{slug?}/achievements', [SystemController::class, 'achievements'])
            //     ->name('system.achievement.index');
            Route::get('achievement/{achievement}/comments', [CommentController::class, 'indexForAchievement'])->name('achievement.comments');
            Route::get('achievement/{achievement}/tickets', [TicketController::class, 'indexForAchievement'])->name('achievement.tickets');

            // Route::get('game/{game}{slug?}', [GameController::class, 'show'])->name('game.show');
            // Route::resource('games', GameController::class)->only('index')->names(['index' => 'game.index']);
            // Route::get('games/popular', [GameController::class, 'popular'])->name('games.popular');
            Route::get('games/suggest', SuggestGameController::class)->name('games.suggest');
            // Route::get('game/{game}/badges', [GameBadgeController::class, 'index'])->name('game.badge.index');
            // Route::get('game/{game}/assets', [GameAssetsController::class, 'index'])->name('game.asset.index');
            // Route::get('game/{game}/players', [GamePlayerController::class, 'index'])->name('game.player.index');
            Route::get('game/{game}/comments', [CommentController::class, 'indexForGame'])->name('game.comments');
            Route::get('game/{game}/dev-interest', GameDevInterestController::class)->name('game.dev-interest');
            Route::get('game/{game}/modification-comments', [CommentController::class, 'indexForGameModifications'])->name('game.modification-comments');
            Route::get('game/{game}/suggest', [SuggestGameController::class, 'forGame'])->name('game.suggest');
            Route::get('game/{game}/tickets', [TicketController::class, 'indexForGame'])->name('game.tickets');

            Route::get('achievement/{achievement}/tickets/create', [AchievementController::class, 'createTicket'])->name('achievement.create-ticket');
            Route::get('achievement/{achievement}/report-issue', [AchievementController::class, 'reportIssue'])->name('achievement.report-issue');

            // Route::get('create', CreateController::class)->name('create');
            // Route::resource('developers', DeveloperController::class)->only('index');

            // Route::resource('game-hashes', GameHashController::class)->only('index')->names(['index' => 'game-hash.index']);

            Route::get('ranking/beaten-games', BeatenGamesLeaderboardController::class)->name('ranking.beaten-games');

            // Route::resource('leaderboards', LeaderboardController::class)->only('index')->names(['index' => 'leaderboard.index']);
            // Route::resource('leaderboard', LeaderboardController::class)->only('show');
            Route::get('leaderboard/{leaderboard}/comments', [CommentController::class, 'indexForLeaderboard'])->name('leaderboard.comments');

            // Route::get('user/{user}/history', [PlayerHistoryController::class, 'show'])->name('user.history');
            Route::get('user/{user}/progress', PlayerCompletionProgressController::class)->name('user.completion-progress');
            Route::get('user/{user}/developer/feed', DeveloperFeedController::class)->name('developer.feed');
            Route::get('user/{user}/developer/sets', DeveloperSetsController::class)->name('developer.sets');
            Route::get('user/{user}/tickets', [TicketController::class, 'indexForDeveloper'])->name('developer.tickets');
            Route::get('user/{user}/tickets/feedback', [TicketController::class, 'indexForReporterFeedback'])->name('reporter.tickets');
            Route::get('user/{user}/tickets/resolved-for-others', [TicketController::class, 'indexForDeveloperResolvedForOthers'])->name('developer.tickets.resolved-for-others');

            // Route::resource('user.achievements', PlayerAchievementController::class)->only('index')->names(['index' => 'user.achievement.index']);
            // Route::resource('user.games', PlayerGameController::class)->only('index')->names(['index' => 'user.game.index']);
            // Route::resource('user.game', PlayerGameController::class)->only('show');
            Route::get('user/{user}/game/{game}/activity', [PlayerGameController::class, 'activity'])->name('user.game.activity');
            Route::get('user/{user}/game/{game}/compare', CompareUnlocksController::class)->name('game.compare-unlocks');

            // Route::resource('user.badges', PlayerBadgeController::class)->only('index')->names(['index' => 'user.badge.index']);
            // Route::resource('user.badge', PlayerBadgeController::class)->only('show');

            // Route::get('tools', [ToolController::class, 'index'])->name('tool.index');
            // Route::get('tool/hash-check', [ToolController::class, 'hashCheck'])->name('tool.hash-check');

            Route::group([
                'middleware' => ['auth'], // TODO: 'verified'
            ], function () {
                Route::resource('game-hash', GameHashController::class)->parameters(['game-hash' => 'gameHash'])->only(['update', 'destroy']);

                // Route::get('user/{user}/game/{game}/compare', [PlayerGameController::class, 'compare'])
                //     ->name('user.game.compare');
            });
        });
    }
}
