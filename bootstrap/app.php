<?php

declare(strict_types=1);

use App\Console\Kernel as ConsoleKernel;
use App\Exceptions\Handler;
use App\Http\Kernel as HttpKernel;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return tap(
    Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([
            Illuminate\Http\Middleware\ValidatePathEncoding::class,
            Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class,
            App\Http\Middleware\TrustHosts::class,
            App\Http\Middleware\TrustProxies::class,
            Illuminate\Http\Middleware\HandleCors::class,
            App\Http\Middleware\HandleCloudflareChallenge::class,
            App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            Illuminate\Http\Middleware\ValidatePostSize::class,
            App\Http\Middleware\TrimStrings::class,
            Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            App\Http\Middleware\RedirectsMissingPages::class,
            App\Http\Middleware\RobotsMiddleware::class,
            App\Http\Middleware\FeatureFlagMiddleware::class,
        ]);

        $middleware->group('web', [
            App\Http\Middleware\ForceHttps::class,
            App\Http\Middleware\EncryptCookies::class,
            Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            Illuminate\Session\Middleware\StartSession::class,
            Illuminate\Session\Middleware\AuthenticateSession::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
            App\Http\Middleware\PreventRequestForgery::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            App\Http\Middleware\UserPreferences::class,
            App\Http\Middleware\UpdateLastActivityAt::class,
            App\Http\Middleware\CheckBanned::class,
        ]);

        $middleware->group('api', [
            App\Api\Middleware\AccessControlAllowOriginWildcard::class,
            'json',
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->group('connect', [
            'json',
            Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => App\Http\Middleware\Authenticate::class,
            'auth.basic' => Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => Illuminate\Auth\Middleware\Authorize::class,
            'guest' => App\Http\Middleware\RedirectIfAuthenticated::class,
            'inertia' => App\Http\Middleware\HandleInertiaRequests::class,
            'jsonapi' => LaravelJsonApi\Laravel\Http\Middleware\BootJsonApi::class,
            'password.confirm' => Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => App\Http\Middleware\ValidateSignature::class,
            'throttle' => Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'csp' => Spatie\Csp\AddCspHeaders::class,
            'json' => App\Http\Middleware\JsonResponse::class,
            'cacheResponse' => Spatie\ResponseCache\Middlewares\CacheResponse::class,
        ]);

        $middleware->priority([
            Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            Illuminate\Cookie\Middleware\EncryptCookies::class,
            Illuminate\Session\Middleware\StartSession::class,
            Illuminate\View\Middleware\ShareErrorsFromSession::class,
            Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            Illuminate\Routing\Middleware\ThrottleRequests::class,
            Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
            Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
            Illuminate\Routing\Middleware\SubstituteBindings::class,
            Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->create(),

    function (Application $app): void {
        $app->singleton(HttpKernelContract::class, HttpKernel::class);
        $app->singleton(ConsoleKernelContract::class, ConsoleKernel::class);
        $app->singleton(ExceptionHandlerContract::class, Handler::class);
    },
);
