<?php

declare(strict_types=1);

namespace App\Community;

use App\Community\Controllers\ContactController;
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
                Route::get('contact', ContactController::class)->name('contact');

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
                // Route::resource('game.comments', GameCommentController::class)->only('index')->names(['index' => 'game.comment.index']);
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

                //     // Route::get('posts/clearing', [ForumCommentController::class, 'clearing'])->name('forum-topic-comments.clearing');
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
                // Route::group([
                //     'middleware' => ['auth', 'verified'],
                // ], function () {
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
                //     // additional "delete all" route
                //     Route::delete('user/{user}/comments', [UserCommentController::class, 'destroyAll'])->name('user.comment.destroyAll');

                //     /*
                //      * "My" friends
                //      */
                //     Route::get('activity', [UserActivityController::class, 'index'])->name('activity');
                //     Route::resource('friends', FriendController::class)->only('index')->names(['index' => 'friend.index']);
                //     Route::resource('relation', UserRelationController::class)->only('store', 'update', 'destroy');

                //     // Route::get('history', [PlayerHistoryController::class, 'index'])->name('history.index');

                //     /*
                //      * inbox
                //      */
                //     Route::resource('message', MessageController::class)->except('index', 'edit', 'update');
                //     Route::resource('messages', MessageController::class)->only('index')->names(['index' => 'message.index']);

                //     /*
                //      * tickets
                //      */
                //     // Route::resource('ticket', TicketController::class)->except('index', 'create');
                //     // Route::resource('achievement.ticket', AchievementTicketController::class)->only('create');
                //     // Route::group([
                //     //     'prefix' => 'achievements',
                //     // ], function () {
                //     //     Route::resource('tickets', AchievementTicketController::class)->only('index')
                //     //     ->names(['index' => 'achievements.ticket.index']);
                //     // });
                //     //
                //     // Route::resource('leaderboard.ticket', LeaderboardTicketController::class)->only('create');
                //     // Route::group([
                //     //     'prefix' => 'leaderboards',
                //     // ], function () {
                //     //     Route::resource('tickets', LeaderboardTicketController::class)->only('index')
                //     //         ->names(['index' => 'leaderboards.ticket.index']);
                //     // });
                //     Route::resource('trigger.ticket', TriggerTicketController::class)->only('create');
                //     Route::group(['prefix' => 'triggers'], function () {
                //         Route::resource('tickets', TriggerTicketController::class)
                //             ->only('index')
                //             ->names(['index' => 'triggers.ticket.index']);
                //     });

                //     Route::resource('news', NewsController::class)
                //         ->only(
                //             'create',
                //             'store',
                //             'edit',
                //             'update',
                //             'destroy'
                //         );
                //     Route::delete('news/{news}/image', [NewsController::class, 'destroyImage'])
                //         ->name('news.image.destroy');
                // });
            });
    }
}
