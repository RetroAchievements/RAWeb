<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Controllers\WebApiController;
use App\Api\Controllers\WebApiV1Controller;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120));
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
        Route::middleware(['api', 'auth:api-token'])->prefix('API')->group(function () {
            /*
             * Usually called via GET, should allow POST, too though
             */
            Route::any('{method}.php', [WebApiV1Controller::class, 'request']);
            Route::any('{method}', [WebApiV1Controller::class, 'request']);
            /*
             * Nothing to do on root level
             */
            Route::any('/', [WebApiController::class, 'noop']);
        });
    }

    private function apiDomain(): ?string
    {
        if ($domain = parse_url(config('app.api_url'), PHP_URL_HOST)) {
            if (is_string($domain)) {
                return $domain;
            }
        }

        if ($domain = parse_url(config('app.url'), PHP_URL_HOST)) {
            if (is_string($domain)) {
                return $domain;
            }
        }

        return null;
    }

    private function apiPrefix(): ?string
    {
        if ($prefix = parse_url(config('app.api_url'), PHP_URL_PATH)) {
            if (is_string($prefix)) {
                return $prefix;
            }
        }

        return null;
    }
}
