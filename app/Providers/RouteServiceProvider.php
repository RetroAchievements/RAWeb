<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Concerns\HandlesPublicFileRequests;
use App\Http\Controllers\Api\SearchApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\DownloadsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\UserController;
use App\Models\Game;
use App\Models\User;
use App\Platform\Actions\BuildGameShowPagePropsAction;
use App\Platform\Actions\LoadGameWithRelationsAction;
use App\Platform\Controllers\GameController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Octane\Facades\Octane;

class RouteServiceProvider extends ServiceProvider
{
    use HandlesPublicFileRequests;

    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     */
    public const HOME = '/';

    public function boot(): void
    {
        /*
         * sanitize route model binding patterns
         */
        Route::pattern('slug', '-[a-zA-Z0-9_-]+');
        Route::pattern('user', '[a-zA-Z0-9_]{1,20}');
    }

    public function map(): void
    {
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware(['web'])->group(function () {
            // prohibit GET form requests in request/
            Route::get('request/{path}.php', fn (string $path) => abort(405))->where('path', '(.*)');
            Route::post('request/{path}.php', fn (string $path) => $this->handleRequest('request/' . $path))->where('path', '(.*)');
        });

        Route::get('rss-{feed}', fn ($feed) => $this->handleRequest('rss-' . $feed . '.xml'));

        Route::middleware(['web', 'csp'])->group(function () {
            Route::get('{path}.php', fn (string $path) => $this->handlePageRequest($path))->where('path', '(.*)');
            Route::get('user/{user}', fn (string $user) => $this->handlePageRequest('userInfo', $user))->name('user.show');
            Route::get('achievement/{achievementId}{slug?}', fn ($achievementId) => $this->handlePageRequest('achievementInfo', $achievementId))->name('achievement.show');
            Route::get('leaderboard/{leaderboard}{slug?}', fn ($leaderboard) => $this->handlePageRequest('leaderboardinfo', $leaderboard))->name('leaderboard.show');
        });

        // Intelligently serves different pages based whether the user has enabled beta features.
        Route::middleware(['web', 'csp', 'inertia'])->group(function () {
            Route::get('game/{game}{slug?}', function ($game) {
                /** @var User $user */
                $user = Auth::user();

                if ($user->enable_beta_features) {
                    $gameModel = Game::findOrFail($game);
                    $controller = app(GameController::class);

                    return $controller->show(
                        request(),
                        $gameModel,
                        app(LoadGameWithRelationsAction::class),
                        app(BuildGameShowPagePropsAction::class)
                    );
                }

                // For non-beta users, we need to bypass Inertia and serve the legacy page.
                request()->merge(['game' => $game]);

                // Return a non-Inertia response that will bypass Inertia middleware.
                return response()->view('pages-legacy.gameInfo');
            })->name('game.show');
        });

        Route::middleware(['web', 'csp'])->group(function () {
            /*
             * content
             */
            Route::middleware(['inertia'])->group(function () {
                Route::get('/', [HomeController::class, 'index'])->name('home');

                Route::get('downloads', [DownloadsController::class, 'index'])->name('download.index');

                Route::get('contact', fn () => Inertia::render('contact'))->name('contact');
                Route::get('rss', fn () => Inertia::render('rss'))->name('rss.index');
                Route::get('terms', fn () => Inertia::render('terms'))->name('terms');
            });
            // Route::get('feed', [FeedController::class, 'index'])->name('feed.index');
            // Route::get('rss/{resource}', [RssController::class, 'show'])->name('rss.show');
            // Route::get('search', [SearchController::class, 'index'])->name('search');

            /*
             * Octane test route
             */
            Octane::route('GET', '/octane', fn () => response('Octane'));

            /*
             * redirects
             */
            Route::get('redirect', [RedirectController::class, 'redirect'])->name('redirect');

            /*
             * user & permalinks
             */
            // Route::resource('user', UserController::class)->only('show');
            // Route::resource('users', UserController::class)->only('index')->names(['index' => 'user.index']);
            Route::get('u/{hashId}', [UserController::class, 'permalink'])->name('user.permalink');

            Route::group([
                'prefix' => 'internal-api',
            ], function () {
                Route::get('search', [SearchApiController::class, 'index'])->name('api.search.index');
            });

            /*
             * protected routes, need an authenticated user with a verified email address
             * permissions are checked in controllers individually by authorizing abilities in the respective controller actions
             */
            Route::group([
                'middleware' => ['auth'],
                'prefix' => 'internal-api',
            ], function () {
                // Route::get('notifications', [NotificationsController::class, 'index'])->name('notification.index');

                Route::put('user/forum-permissions', [UserApiController::class, 'updateForumPostPermissions'])->name('api.user.forum-permissions.update');

                Route::post('delete-request', [UserController::class, 'requestAccountDeletion'])->name('api.user.delete-request.store');
                Route::delete('delete-request', [UserController::class, 'cancelAccountDeletion'])->name('api.user.delete-request.destroy');

                Route::post('avatar', [UserController::class, 'uploadAvatar'])->name('api.user.avatar.store');
                Route::delete('avatar', [UserController::class, 'deleteAvatar'])->name('api.user.avatar.destroy');
            });
        });
    }
}
