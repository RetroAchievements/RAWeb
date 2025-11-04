<?php

declare(strict_types=1);

namespace App\Connect;

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
        // TODO $this->mvcConnectRoutes();

        $this->rpcConnectRoutes();
    }

    private function rpcConnectRoutes(): void
    {
        /*
         * Connect RPC API routes for client integrations (RAIntegration, RetroArch).
         * These routes use the HandlesPublicFileRequests trait to load public PHP files.
         *
         * Route::any() is used because different clients use different HTTP methods.
         * RAIntegration uses POST requests, except for LatestIntegration.html.
         * Legacy RetroArch uses GET requests. RetroArch 1.9.13+ (Nov 2021) should use POST.
         *
         * Note: Don't apply 'auth:connect-token' guard via middleware for the whole of the RPC API.
         * There are public routes in there.
         *
         * TODO: Eventually migrate to a proper controller-based architecture.
         * Previously attempted with ConnectApiController and related Concerns
         * (AchievementRequests, BootstrapRequests, HeartbeatRequests, LeaderboardRequests,
         * LegacyCompatProxyRequests), but removed as it wasn't the right direction at this time.
         * Future implementation should follow standard Laravel MVC patterns and ideally host
         * the Connect API on a dedicated subdomain (see connectDomain/connectPrefix methods below)
         * to cleanly separate RPC traffic from the main web application. This work was previously
         * started in the site's V2 release (circa 2022), but ultimately removed because PHPStan was 
         * complaining about dead code.
         * @see https://github.com/RetroAchievements/RAWeb/blob/d81dfbfd06d3233f73168546467e3e6c8006d124/app/Connect/Controllers/ConnectApiController.php
         */
        Route::middleware(['connect'])->group(function () {
            Route::any('login_app.php', fn () => $this->handleRequest('login_app'));
            Route::any('dorequest.php', fn () => $this->handleRequest('dorequest'));
            Route::any('doupload.php', fn () => $this->handleRequest('doupload'));
        });
    }
}
