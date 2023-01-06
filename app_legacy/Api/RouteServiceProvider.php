<?php

declare(strict_types=1);

namespace LegacyApp\Api;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use LegacyApp\Api\Middleware\LogApiUsage;
use LegacyApp\Support\Http\HandlesPublicFileRequests;

class RouteServiceProvider extends ServiceProvider
{
    use HandlesPublicFileRequests;

    public function boot(): void
    {
        // TODO setup rate limiting
        // RateLimiter::for('api', fn (Request $request) => Limit::perMinute(90));
    }

    public function map(): void
    {
        Route::middleware(['api', 'auth:api-token-legacy', LogApiUsage::class])->prefix('API')->group(function () {
            Route::any('{method}.php', fn (string $method) => $this->handleRequest('API/' . $method))->where('path', '(.*)');
        });
    }
}
