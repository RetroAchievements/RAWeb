<?php

declare(strict_types=1);

namespace App\Community;

use App\Community\Controllers\AchievementCommentController;
use App\Community\Controllers\AchievementSetClaimController;
use App\Community\Controllers\Api\AchievementCommentApiController;
use App\Community\Controllers\Api\GameCommentApiController;
use App\Community\Controllers\Api\LeaderboardCommentApiController;
use App\Community\Controllers\Api\SubscriptionApiController;
use App\Community\Controllers\Api\UserCommentApiController;
use App\Community\Controllers\Api\UserGameListApiController;
use App\Community\Controllers\ForumTopicCommentController;
use App\Community\Controllers\ForumTopicController;
use App\Community\Controllers\GameCommentController;
use App\Community\Controllers\LeaderboardCommentController;
use App\Community\Controllers\MessageController;
use App\Community\Controllers\MessageThreadController;
use App\Community\Controllers\UserCommentController;
use App\Community\Controllers\UserForumTopicCommentController;
use App\Community\Controllers\UserGameListController;
use App\Community\Controllers\UserSettingsController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * sanitize route model binding patterns
         */
        Route::pattern('topic', '[0-9]{1,17}');
        Route::pattern('forum', '[0-9]{1,17}');
        Route::pattern('category', '[0-9]{1,17}');
        Route::pattern('comment', '[0-9]{1,17}');
        Route::pattern('news', '[0-9]{1,17}');

        parent::boot();
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web', 'csp'])
            ->group(function () {
                /*
                 * client-side api calls
                 */
                Route::middleware(['auth'])->group(function () {
                    Route::group(['prefix' => 'internal-api'], function () {
                        Route::post('achievement/{achievement}/comment', [AchievementCommentApiController::class, 'store'])->name('api.achievement.comment.store');
                        Route::post('game/{game}/comment', [GameCommentApiController::class, 'store'])->name('api.game.comment.store');
                        Route::post('leaderboard/{leaderboard}/comment', [LeaderboardCommentApiController::class, 'store'])->name('api.leaderboard.comment.store');
                        Route::post('user/{user}/comment', [UserCommentApiController::class, 'store'])->name('api.user.comment.store');
                        Route::delete('achievement/{achievement}/comment/{comment}', [AchievementCommentApiController::class, 'destroy'])->name('api.achievement.comment.destroy');
                        Route::delete('game/{game}/comment/{comment}', [GameCommentApiController::class, 'destroy'])->name('api.game.comment.destroy');
                        Route::delete('leaderboard/{leaderboard}/comment/{comment}', [LeaderboardCommentApiController::class, 'destroy'])->name('api.leaderboard.comment.destroy');
                        Route::delete('user/{user}/comment/{comment}', [UserCommentApiController::class, 'destroy'])->name('api.user.comment.destroy');

                        Route::post('subscription/{subjectType}/{subjectId}', [SubscriptionApiController::class, 'store'])->name('api.subscription.store');
                        Route::delete('subscription/{subjectType}/{subjectId}', [SubscriptionApiController::class, 'destroy'])->name('api.subscription.destroy');

                        Route::get('user-game-list', [UserGameListApiController::class, 'index'])->name('api.user-game-list.index');
                        Route::post('user-game-list/{game}', [UserGameListApiController::class, 'store'])->name('api.user-game-list.store');
                        Route::delete('user-game-list/{game}', [UserGameListApiController::class, 'destroy'])->name('api.user-game-list.destroy');
                    });
                });

                Route::middleware(['inertia'])->group(function () {
                    Route::get('achievement/{achievement}/comments', [AchievementCommentController::class, 'index'])->name('achievement.comment.index');
                    Route::get('game/{game}/comments', [GameCommentController::class, 'index'])->name('game.comment.index');
                    Route::get('leaderboard/{leaderboard}/comments', [LeaderboardCommentController::class, 'index'])->name('leaderboard.comment.index');
                    Route::get('user/{user}/comments', [UserCommentController::class, 'index'])->name('user.comment.index');

                    Route::get('forums/recent-posts', [ForumTopicController::class, 'recentPosts'])->name('forum.recent-posts');

                    Route::get('user/{user}/posts', [UserForumTopicCommentController::class, 'index'])->name('user.posts.index');

                    Route::get('settings', [UserSettingsController::class, 'show'])->name('settings.show');
                });

                /*
                 * shallow comment routes - keep comments at the root level, not nested (topic.comment, user.comment, achievement.comment)
                 * -> deeplinks & legacy links
                 */
                // Route::resource('comment', CommentController::class)->only('show', 'edit', 'update', 'destroy');

                /*
                 * nested comment routes
                 */
                // Route::resource('achievement.comments', AchievementCommentController::class)->only('index')->names(['index' => 'achievement.comment.index']);
                // Route::group(['prefix' => 'achievements'], function () {
                //     Route::resource('comment', AchievementCommentController::class)->only('show')->names(['show' => 'achievement.comment.show'])->shallow();
                // });
                // Route::group(['prefix' => 'games'], function () {
                //     Route::resource('comment', GameCommentController::class)->only('show')->names(['show' => 'game.comment.show'])->shallow();
                // });
                // Route::resource('news.comments', NewsCommentController::class)->only('index')->names(['index' => 'news.comment.index']);
                // Route::group(['prefix' => 'news'], function () {
                //     Route::resource('comment', NewsCommentController::class)->only('show')->names(['show' => 'news.comment.show'])->shallow();
                // });
                // Route::resource('user.comments', UserCommentController::class)->only('index')->names(['index' => 'user.comment.index']);
                // Route::group(['prefix' => 'users'], function () {
                //     Route::resource('comment', UserCommentController::class)->only('show')->names(['show' => 'user.comment.show'])->shallow();
                // });

                /*
                 * forums
                 * TODO resource specific discussions
                 */
                // Route::get('system/{system}/{systemSlug}/discussions', [ForumController::class, 'system'])->name('system.discussions');
                // Route::get('game/{game}/{gameSlug}/discussions', [ForumController::class, 'game'])->name('game.discussions');

                // Route::group([
                //     'prefix' => 'forums',
                // ], function () {
                //     Route::get('/', [ForumCategoryController::class, 'index'])->name('forum.index');

                //     Route::get('category/{category}{slug?}', [ForumCategoryController::class, 'show'])
                //         ->name('forum-category.show');
                //     Route::get('forum/{forum}{slug?}', [ForumController::class, 'show'])->name('forum.show');
                //     Route::get('topics', [ForumTopicController::class, 'index'])->name('forum-topic.index');
                //     Route::get('topic/{topic}{slug?}', [ForumTopicController::class, 'show'])
                //         ->name('forum-topic.show');

                //     Route::group(['prefix' => 'topics'], function () {
                //         Route::resource('comment', ForumTopicCommentController::class)
                //             ->only('show')
                //             ->names(['show' => 'forum-topic-comment.show'])
                //             ->shallow();
                //     });

                //     Route::group([
                //         'middleware' => ['auth', 'verified'],
                //     ], function () {
                //         // keep topic comment store nested -> has to be child of a topic
                //         Route::resource('topic.comment', ForumTopicCommentController::class)
                //             ->only('store')->names(['store' => 'forum-topic-comment.store']);

                //         Route::group([
                //             'prefix' => 'topics',
                //         ], function () {
                //             // keep rest of topic comment routes at root level -> topic is eager loaded and not required in route
                //             Route::resource('topic.comment', ForumTopicCommentController::class)
                //                 ->only(
                //                     'edit',
                //                     'update',
                //                     'destroy'
                //                 )
                //                 ->names([
                //                     'edit' => 'forum-topic-comment.edit',
                //                     'update' => 'forum-topic-comment.update',
                //                     'destroy' => 'forum-topic-comment.destroy',
                //                 ])
                //                 ->shallow();
                //         });

                //         // keep forum topic nested for creation -> has to be child of a forum
                //         Route::resource('forum.topic', ForumTopicController::class)->only('create', 'store');

                //         // keep topics
                //         Route::resource('topic', ForumTopicController::class)->only('edit', 'update')
                //             ->names([
                //                 'edit' => 'forum-topic.edit',
                //                 'update' => 'forum-topic.update',
                //             ]);
                //     });

                //     Route::resource('category', ForumCategoryController::class)->only('edit', 'update')
                //         ->names(['edit' => 'forum-category.edit', 'update' => 'forum-category.update']);

                //     Route::resource('forum', ForumController::class)->only('create', 'store', 'edit', 'update');

                //     // Route::get('posts/verify', [ForumCommentController::class, 'verify'])->name('forum-topic-comments.verify');
                // });

                /*
                 * news
                 */
                // Route::resource('news', NewsController::class)->only('index');
                // Route::get('news/{news}{slug?}', [NewsController::class, 'show'])->name('news.show');

                // Route::get('streams', [StreamController::class, 'index'])->name('stream.index');

                /*
                 * social features
                 */
                // Route::resource('user.friends', FriendController::class)->only('index')->names(['index' => 'user.friend.index']);

                /*
                 * protected routes, need an authenticated user with a verified email address
                 * permissions are checked in controllers individually by authorizing abilities in the respective controller actions
                 */
                Route::group([
                    'middleware' => ['auth', 'verified'],
                ], function () {
                    Route::delete('user/{user}/comments', [UserCommentController::class, 'destroyAll'])->name('user.comment.destroyAll');

                //     /*
                //      * commentables
                //      * nested auth comments routes -> no conflicts with id/slug in route
                //      * dedicated create routes would go here, too, but are not used
                //      */
                //     Route::resource('achievement.comment', AchievementCommentController::class)->only('store');
                //     Route::group(['prefix' => 'achievements'], function () {
                //         Route::resource('achievements.comment', AchievementCommentController::class)
                //             ->only(
                //                 'edit',
                //                 'update',
                //                 'destroy'
                //             )
                //             ->names([
                //                 'edit' => 'achievement.comment.edit',
                //                 'update' => 'achievement.comment.update',
                //                 'destroy' => 'achievement.comment.destroy',
                //             ])
                //             ->shallow();
                //     });
                //     Route::resource('game.comment', GameCommentController::class)->only('store');
                //     Route::group(['prefix' => 'games'], function () {
                //         Route::resource('games.comment', GameCommentController::class)
                //             ->only(
                //                 'edit',
                //                 'update',
                //                 'destroy'
                //             )
                //             ->names([
                //                 'edit' => 'game.comment.edit',
                //                 'update' => 'game.comment.update',
                //                 'destroy' => 'game.comment.destroy',
                //             ])
                //             ->shallow();
                //     });
                //     Route::resource('news.comment', NewsCommentController::class)->only('store');
                //     Route::group(['prefix' => 'news'], function () {
                //         Route::resource('news.comment', NewsCommentController::class)
                //             ->only(
                //                 'edit',
                //                 'update',
                //                 'destroy'
                //             )
                //             ->names([
                //                 'edit' => 'news.comment.edit',
                //                 'update' => 'news.comment.update',
                //                 'destroy' => 'news.comment.destroy',
                //             ])
                //             ->shallow();
                //     });
                //     Route::resource('user.comment', UserCommentController::class)->only('store');
                //     Route::group(['prefix' => 'users'], function () {
                //         Route::resource('user.comment', UserCommentController::class)
                //             ->only(
                //                 'edit',
                //                 'update',
                //                 'destroy'
                //             )
                //             ->names([
                //                 'edit' => 'user.comment.edit',
                //                 'update' => 'user.comment.update',
                //                 'destroy' => 'user.comment.destroy',
                //             ])
                //             ->shallow();
                //     });

                //     /*
                //      * "My" friends
                //      */
                //     Route::get('activity', [UserActivityController::class, 'index'])->name('activity');
                //     Route::resource('friends', FriendController::class)->only('index')->names(['index' => 'friend.index']);
                //     Route::resource('relation', UserRelationController::class)->only('store', 'update', 'destroy');

                //     // Route::get('history', [PlayerHistoryController::class, 'index'])->name('history.index');

                });

                /*
                 * game lists
                 */
                Route::group([
                    'middleware' => ['auth', 'inertia'],
                ], function () {
                    Route::get('game-list/play', [UserGameListController::class, 'index'])->name('game-list.play.index');
                });

                /*
                 * claims
                 */
                Route::group([
                    'middleware' => ['auth', 'verified'],
                ], function () {
                    Route::post('game/{game}/claim/create', [AchievementSetClaimController::class, 'store'])->name('achievement-set-claim.create');
                    Route::post('game/{game}/claim/drop', [AchievementSetClaimController::class, 'delete'])->name('achievement-set-claim.delete');
                    Route::post('claim/{claim}/update', [AchievementSetClaimController::class, 'update'])->name('achievement-set-claim.update');
                });

                /*
                 * messages
                 */
                Route::group([
                    'middleware' => ['auth'], // TODO add 'verified' middleware
                ], function () {
                    Route::resource('message', MessageController::class)->only(['store']);
                    Route::resource('message-thread', MessageThreadController::class)->parameter('message-thread', 'messageThread')->only(['destroy']);
                });

                /*
                 * user settings
                 */
                Route::group([
                    'middleware' => ['auth'],
                    'prefix' => 'internal-api/settings',
                ], function () {
                    Route::put('profile', [UserSettingsController::class, 'updateProfile'])->name('api.settings.profile.update');
                    Route::put('locale', [UserSettingsController::class, 'updateLocale'])->name('api.settings.locale.update');
                    Route::put('preferences', [UserSettingsController::class, 'updatePreferences'])->name('api.settings.preferences.update');
                    Route::put('password', [UserSettingsController::class, 'updatePassword'])->name('api.settings.password.update');
                    Route::put('email', [UserSettingsController::class, 'updateEmail'])->name('api.settings.email.update');

                    Route::delete('keys/web', [UserSettingsController::class, 'resetWebApiKey'])->name('api.settings.keys.web.destroy');
                    Route::delete('keys/connect', [UserSettingsController::class, 'resetConnectApiKey'])->name('api.settings.keys.connect.destroy');
                });
            });
    }
}
