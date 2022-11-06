<?php

declare(strict_types=1);

namespace App\Legacy;

use App\Legacy\Controllers\UserController;
use App\Legacy\Middleware\LogApiUsage;
use Exception;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // TODO setup rate limiting
        // RateLimiter::for('api', fn (Request $request) => Limit::perMinute(90));
        // RateLimiter::for('connect', fn (Request $request) => Limit::perMinute(90));
        // RateLimiter::for('web', fn (Request $request) => Limit::perMinute(90));
    }

    public function map(): void
    {
        Route::middleware(['connect'])->group(function () {
            Route::any('login_app.php', fn () => $this->handleRequest('login_app'));
            Route::any('dorequest.php', fn () => $this->handleRequest('dorequest'));
            Route::any('doupload.php', fn () => $this->handleRequest('doupload'));
        });

        Route::middleware(['api', 'auth:api-token-legacy', LogApiUsage::class])->prefix('API')->group(function () {
            Route::any('{method}.php', fn (string $method) => $this->handleRequest('API/' . $method))->where('path', '(.*)');
        });

        Route::middleware(['web'])->group(function () {
            // prohibit GET form requests in request/
            Route::get('request/{path}.php', fn (string $path) => abort(405))->where('path', '(.*)');
            Route::post('request/{path}.php', fn (string $path) => $this->handleRequest('request/' . $path))->where('path', '(.*)');
        });

        Route::get('rss-{feed}', fn ($feed) => $this->handleRequest('rss-' . $feed . '.xml'));

        Route::middleware(['web', 'csp'])->group(function () {
            Route::get('download.php', fn () => $this->handlePageRequest('download'))->name('download.index');
            Route::get('gameList.php', fn () => $this->handlePageRequest('gameList'))->name('game.index');
            Route::post('{path}.php', fn (string $path) => $this->handleRequest($path))->where('path', '(.*)');
            Route::get('{path}.php', fn (string $path) => $this->handlePageRequest($path))->where('path', '(.*)');
            Route::get('/', fn () => $this->handlePageRequest('home'))->name('home');
            Route::get('user/{user}', fn (string $user) => $this->handlePageRequest('userInfo', $user))->name('user.show');
            Route::get('u/{hashId}', [UserController::class, 'permalink'])->name('user.permalink');
            Route::get('achievement/{achievement}{slug?}', fn ($achievement) => $this->handlePageRequest('achievementInfo', $achievement))->name('achievement.show');
            Route::get('game/{game}{slug?}', fn ($game) => $this->handlePageRequest('gameInfo', $game))->name('game.show');
        });
    }

    private function handlePageRequest(string $file, string $resourceId = null): Response|RedirectResponse|View
    {
        if (is_string($resourceId)) {
            $_GET['ID'] = $resourceId;
        }

        $scriptPath = public_path("$file.php");
        if (!file_exists($scriptPath)) {
            throw new NotFoundHttpException();
        }

        ob_start();
        try {
            $response = require_once $scriptPath;

            if ($response instanceof Response) {
                ob_end_clean();

                return $response;
            }

            if ($response instanceof RedirectResponse) {
                ob_end_clean();

                return $response;
            }

            if ($response instanceof View) {
                ob_end_clean();

                return $response;
            }
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        $bufferedOutput = ob_get_clean();

        return view('layouts.app')
            ->with('bufferedOutput', $bufferedOutput);
    }

    private function handleRequest(string $path): Response|RedirectResponse|JsonResponse|StreamedResponse
    {
        $scriptPath = public_path("$path.php");
        if (!file_exists($scriptPath)) {
            throw new NotFoundHttpException();
        }

        $this->runInterceptor($path);

        $response = include_once $scriptPath;

        if ($response instanceof Response) {
            return $response;
        }

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            return $response;
        }

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        return response($response, headers: ['Content-Type' => 'application/json']);
    }

    private function runInterceptor(string $path)
    {
        if (config('interceptor.connect') && $path === 'dorequest') {
            require_once config('interceptor.connect');
        } elseif (config('interceptor.web')) {
            require_once config('interceptor.web');
        }
    }
}
