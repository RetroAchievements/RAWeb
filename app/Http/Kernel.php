<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     * These middlewares are run during every request to your application.
     */
    protected $middleware = [
        \App\Site\Middleware\TrustHosts::class,
        \App\Site\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Site\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Site\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Site\Middleware\RedirectsMissingPages::class,
        \App\Site\Middleware\RobotsMiddleware::class,
        \App\Site\Middleware\FeatureFlagMiddleware::class,
    ];

    /**
     * The application's route middleware groups.
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Site\Middleware\ForceHttps::class,
            \App\Site\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Site\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Site\Middleware\UserPreferences::class,
            // TODO Web Interceptor middleware
            // TODO \Illuminate\Routing\Middleware\ThrottleRequests::class.':web',
        ],

        'api' => [
            \App\Api\Middleware\AccessControlAllowOriginWildcard::class,
            'json',
            // TODO Api Interceptor middleware
            // TODO \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
        ],

        'connect' => [
            'json',
            // TODO Connect Interceptor middleware
            // TODO \Illuminate\Routing\Middleware\ThrottleRequests::class.'throttle:connect',
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Site\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Site\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        /*
         * providers
         */
        'csp' => \Spatie\Csp\AddCspHeaders::class,

        /*
         * Allows to force JSON response regardless of accept header. Not clean but also clean.
         */
        'json' => \App\Site\Middleware\JsonResponse::class,
    ];

    /**
     * The priority-sorted list of middleware.
     * This forces non-global middleware to always be in the given order.
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Site\Middleware\Authenticate::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
