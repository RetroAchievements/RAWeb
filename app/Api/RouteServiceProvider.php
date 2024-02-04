<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Controllers\WebApiController;
use App\Api\Controllers\WebApiV1Controller;
use App\Api\Middleware\LogApiUsage;
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
        $this->apiRoutes();
        /*
         * legacy comes last to prevent it from being served on other subdomains
         */
        $this->legacyCompatRoutes();
    }

    private function apiRoutes(): void
    {
        /*
         * JSON-API (REST)
         * Served from api subdomain in production to be replaceable eventually
         * TODO: move to gRPC? JSON-RPC in gRPC envelopes?
         */
        Route::domain($this->apiDomain())
            ->prefix($this->apiPrefix())
            ->middleware(['api'])
            ->group(function () {

                Route::prefix('v1')->group(function () {
                    Route::middleware(['auth:api-token'])->group(function () {
                        Route::any('{method}', [WebApiV1Controller::class, 'request']);
                    });
                });

                Route::prefix('v2')->group(function () {
                    /*
                     * list the available connect servers for clients
                     */
                    Route::get('connect', [WebApiController::class, 'connectServers']);

                    /*
                     * Passport guarded
                     * Note: To have connected clients have access to the web api, too, the client has to send
                     * auth to both. This is not granted inherently here.
                     */
                    Route::middleware(['auth:passport'])->group(function () {
                        Route::get('users', [WebApiController::class, 'users']);
                    });
                });

                /*
                 * Make sure to register a catch-all, too, to prevent the web app from ever responding
                 */
                Route::any('/{path?}', [WebApiController::class, 'noop'])->where('path', '(.*)');
            });
    }

    private function legacyCompatRoutes(): void
    {
        /*
         * Backwards compatibility for "web" api clients.
         * Only a subset of endpoints that the JSON-API (REST) has.
         * Prefix is always set for those as that's how it used to be.
         * Casing should be handled by web server (API vs api).
         * Always has to be authenticated with an api token.
         */
        Route::middleware(['api', 'auth:api-token', LogApiUsage::class])->prefix('API')->group(function () {
            /*
             * Usually called via GET, should allow POST, too though
             */
            Route::any('{method}.php', fn (string $method) => $this->handleRequest('API/' . $method))->where('path', '(.*)');
            // Route::any('{method}.php', [WebApiV1Controller::class, 'request']);
            // Route::any('{method}', [WebApiV1Controller::class, 'request']);
            /*
             * Nothing to do on root level
             */
            Route::any('/', [WebApiController::class, 'noop']);
        });
    }

    private function apiDomain(): ?string
    {
        if ($domain = parse_url(config('app.api_url'), PHP_URL_HOST)) {
            return $domain;
        }

        if ($domain = parse_url(config('app.url'), PHP_URL_HOST)) {
            return $domain;
        }

        return null;
    }

    private function apiPrefix(): ?string
    {
        if ($prefix = parse_url(config('app.api_url'), PHP_URL_PATH)) {
            return $prefix;
        }

        return null;
    }
}
