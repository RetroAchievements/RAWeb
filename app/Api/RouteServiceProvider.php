<?php

declare(strict_types=1);

namespace App\Api;

use App\Api\Internal\Controllers\AchievementController;
use App\Api\Internal\Controllers\HealthController;
use App\Api\Middleware\AddContentLengthHeader;
use App\Api\Middleware\LogApiRequest;
use App\Api\Middleware\LogLegacyApiUsage;
use App\Api\Middleware\ServiceAccountOnly;
use App\Api\V1\Controllers\WebApiV1Controller;
use App\Api\V2\Controllers\WebApiController;
use App\Http\Concerns\HandlesPublicFileRequests;
use App\Models\Achievement;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Core\Exceptions\JsonApiException;

class RouteServiceProvider extends ServiceProvider
{
    use HandlesPublicFileRequests;

    public function boot(): void
    {
        Route::bind('achievement', function ($value) {
            $achievement = Achievement::find($value);

            if (!$achievement) {
                throw JsonApiException::error([
                    'status' => '404',
                    'code' => 'not_found',
                    'title' => 'Not Found',
                    'detail' => "No achievement found with ID {$value}.",
                ]);
            }

            return $achievement;
        });
    }

    public function map(): void
    {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void
    {
        $this->apiRoutes();
        /**
         * legacy comes last to prevent it from being served on other subdomains
         */
        $this->legacyCompatRoutes();
    }

    private function apiRoutes(): void
    {
        /**
         * JSON-API (REST)
         * Served from api subdomain in production to be replaceable eventually
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

                // TODO JSON:API
                Route::prefix('v2')->group(function () {
                    /**
                     * list the available connect servers for clients
                     */
                    Route::get('connect', [WebApiController::class, 'connectServers']);

                    /**
                     * Passport guarded
                     * Note: To have connected clients have access to the web api, too, the client has to send
                     * auth to both. This is not granted inherently here.
                     */
                    Route::middleware(['auth:passport'])->group(function () {
                        Route::get('users', [WebApiController::class, 'users']);
                    });
                });

                /**
                 * Internal API routes for service-to-service communication
                 * Restricted to specific service accounts (eg: RABot).
                 * This is not intended for regular users to access.
                 */
                Route::prefix('internal')->group(function () {
                    $rateLimit = config('internal-api.rate_limit.requests', 60) . ',' . config('internal-api.rate_limit.minutes', 1);

                    Route::middleware([
                        LogApiRequest::class . ':internal',
                        'auth:api-token-header',
                        ServiceAccountOnly::class,
                        AddContentLengthHeader::class,
                        'throttle:' . $rateLimit,
                    ])->group(function () {
                        Route::patch('achievements/{achievement}', [AchievementController::class, 'update'])
                            ->name('api.internal.achievements.update');

                        Route::get('health', [HealthController::class, 'check'])->name('api.internal.health');

                        // Add more internal service routes here as needed.
                    });
                });

                /**
                 * Make sure to register a catch-all, too, to prevent the web app from ever responding
                 */
                Route::any('/{path?}', [WebApiController::class, 'noop'])->where('path', '(.*)');

            });
    }

    private function legacyCompatRoutes(): void
    {
        /**
         * Backwards compatibility for "web" api clients.
         * Only a subset of endpoints that the JSON-API (REST) has.
         * Prefix is always set for those as that's how it used to be.
         * Casing should be handled by web server (API vs api).
         * Always has to be authenticated with an api token.
         */
        Route::middleware(['api', 'auth:api-token', LogLegacyApiUsage::class])->prefix('API')->group(function () {
            /**
             * Usually called via GET, should allow POST, too though
             */
            Route::any('{method}.php', fn (string $method) => $this->handleRequest('API/' . $method))->where('path', '(.*)');
            // Route::any('{method}.php', [WebApiV1Controller::class, 'request']);
            // Route::any('{method}', [WebApiV1Controller::class, 'request']);
            /**
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
