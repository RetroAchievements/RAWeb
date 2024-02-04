<?php

declare(strict_types=1);

namespace App\Connect;

use App\Connect\Controllers\ConnectApiController;
use App\Http\Concerns\HandlesPublicFileRequests;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    use HandlesPublicFileRequests;

    public function boot(): void
    {
    }

    public function map(): void
    {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void
    {
        $this->connectRoutes();

        /*
         * legacy comes last to prevent it from being served on other subdomains
         */
        $this->legacyCompatRoutes();
    }

    private function connectRoutes(): void
    {
        /*
         * RPC/JSON-RPC for client integrations
         * Served from connect subdomain in production to be replaceable eventually
         */
        Route::domain($this->connectDomain())
            ->prefix($this->connectPrefix())
            ->middleware(['connect'])
            ->group(function () {
                /*
                 * Main entrypoint for Connect RPC calls
                 */
                Route::post('/', [ConnectApiController::class, 'request']);

                /*
                 * Make sure to register a catch-all, too, to prevent the web app from ever responding
                 */
                Route::any('/{path?}', [ConnectApiController::class, 'noop'])->where('path', '(.*)');
            });
    }

    private function legacyCompatRoutes(): void
    {
        /*
         * Backwards compatibility for "rpc" api client integrations.
         * The new APIs split Web App from RPC from API and are hosted on their own subdomain each.
         * Legacy endpoints are served from web app domain :/ -> lock into appDomain().
         * We don't want legacy endpoints on the dedicated subdomain or vice versa.
         * Otherwise we'd have to maintain/support them there, too - have a clean cut instead
         * Force API/RPC context for these routes by allowing Route::any() methods.
         * Otherwise the web app may respond with html.
         * Check method and fail within action instead to make sure it will be a json response.
         * E.g. app_login.php never listened for GET requests -> throw a MethodNotAllowedException
         *
         * Note: Don't apply 'auth:connect-token' guard via middleware for the whole of the RPC API.
         * There are public routes in there.
         * Authorize in controllers where needed; Auth::shouldUse('connect-token'); is applied.
         */
        Route::middleware(['connect'])->group(function () {
            /*
             * RAIntegration uses POST requests, except for LatestIntegration.html
             * Legacy RetroArch uses GET requests except, RetroArch 1.9.13+ (Nov 2021) should be using POST for everything
             */
            Route::any('login_app.php', fn () => $this->handleRequest('login_app'));
            Route::any('dorequest.php', fn () => $this->handleRequest('dorequest'));
            Route::any('doupload.php', fn () => $this->handleRequest('doupload'));
            // Route::any('LatestIntegration.html', [ConnectApiController::class, 'legacyLatestIntegration']);
            // Route::any('login_app.php', [ConnectApiController::class, 'legacyLogin']);
            // Route::any('dorequest.php', [ConnectApiController::class, 'legacyRequest']);
            // Route::any('doupload.php', [ConnectApiController::class, 'legacyBadgeUploadRequest']);
        });
    }

    private function connectDomain(): ?string
    {
        if ($domain = parse_url(config('app.connect_url'), PHP_URL_HOST)) {
            return $domain;
        }

        if ($domain = parse_url(config('app.url'), PHP_URL_HOST)) {
            return $domain;
        }

        return null;
    }

    private function connectPrefix(): ?string
    {
        if ($prefix = parse_url(config('app.connect_url'), PHP_URL_PATH)) {
            return $prefix;
        }

        return null;
    }
}
