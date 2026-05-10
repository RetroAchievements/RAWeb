<?php

declare(strict_types=1);

namespace App\Connect;

use App\Http\Concerns\HandlesPublicFileRequests;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RouteServiceProvider extends ServiceProvider
{
    use HandlesPublicFileRequests;

    private const array DEVELOPER_PUBLISH_REQUEST_TYPES = [
        'submitcodenote',
        'submitgametitle',
        'submitrichpresence',
        'uploadachievement',
        'uploadleaderboard',
    ];

    private const array LOGIN_REQUEST_TYPES = [
        'login',
        'login2',
    ];

    private const int PUBLISH_PER_MINUTE = 600;
    private const int DEFAULT_PER_MINUTE = 180;
    private const int LOGIN_PER_IP = 30;
    private const int LOGIN_PER_USER_IP = 5;

    public function boot(): void
    {
        $this->configureRateLimiting();
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
            Route::any('dorequest.php', fn () => $this->handleRequest('dorequest'))
                ->middleware('throttle:connect-dorequest');
            Route::any('doupload.php', fn () => $this->handleRequest('doupload'));
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('connect-dorequest', function (Request $request): array|Limit {
            $requestType = $request->input('r');

            if (in_array($requestType, self::DEVELOPER_PUBLISH_REQUEST_TYPES, true)) {
                return $this->connectLimit(self::PUBLISH_PER_MINUTE, 'publish', $this->connectRateLimitIdentifier($request));
            }

            if (in_array($requestType, self::LOGIN_REQUEST_TYPES, true)) {
                return $this->loginLimits($request);
            }

            return $this->connectLimit(self::DEFAULT_PER_MINUTE, 'default', $this->connectRateLimitIdentifier($request));
        });
    }

    /**
     * Two layered limits: one per (username, IP) so shared NAT can't lock out
     * legitimate users, and one per IP so an attacker can't bypass the first by
     * spraying many usernames from a single host. Based on Fortify's implementation.
     *
     * @return array{0: Limit, 1: Limit}
     */
    private function loginLimits(Request $request): array
    {
        $username = Str::transliterate(Str::lower((string) $request->input('u', '')));
        $ip = $this->ipBucket($request);

        return [
            $this->connectLimit(self::LOGIN_PER_IP, 'login-ip', 'ip:' . $ip),
            $this->connectLimit(self::LOGIN_PER_USER_IP, 'login', ($username ?: 'noname') . '|ip:' . $ip),
        ];
    }

    private function connectLimit(int $perMinute, string $bucket, string $identifier): Limit
    {
        return Limit::perMinute($perMinute)
            ->by("connect:{$bucket}:{$identifier}")
            ->response(fn (Request $request, array $headers): JsonResponse => response()->json([
                'Success' => false,
                'Error' => 'Too Many Attempts',
                'Status' => Response::HTTP_TOO_MANY_REQUESTS,
            ], Response::HTTP_TOO_MANY_REQUESTS, $headers));
    }

    private function connectRateLimitIdentifier(Request $request): string
    {
        $user = $request->user('connect-token');

        if ($user) {
            return 'user:' . $user->getAuthIdentifier();
        }

        return 'ip:' . $this->ipBucket($request);
    }

    /**
     * IPv6 hosts typically receive a whole /64 from their ISP and can rotate
     * source addresses inside it for free. Bucket the prefix so per-IP caps
     * actually constrain a single attacker on IPv6.
     */
    private function ipBucket(Request $request): string
    {
        $ip = $request->ip() ?? 'unknown';

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }

        $packed = inet_pton($ip);

        return inet_ntop(substr($packed, 0, 8) . str_repeat("\0", 8)) . '/64';
    }
}
