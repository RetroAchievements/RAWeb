<?php

declare(strict_types=1);

namespace LegacyApp\Connect;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use LegacyApp\Support\Http\HandlesPublicFileRequests;

class RouteServiceProvider extends ServiceProvider
{
    use HandlesPublicFileRequests;

    public function boot(): void
    {
        // TODO setup rate limiting
        // RateLimiter::for('connect', fn (Request $request) => Limit::perMinute(90));
    }

    public function map(): void
    {
        Route::middleware(['connect'])->group(function () {
            Route::any('login_app.php', fn () => $this->handleRequest('login_app'));
            Route::any('dorequest.php', fn () => $this->handleRequest('dorequest'));
            Route::any('doupload.php', fn () => $this->handleRequest('doupload'));
        });
    }
}
