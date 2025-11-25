<?php

declare(strict_types=1);

namespace App\Providers;

use App\Data\AuthorizeDevicePagePropsData;
use App\Data\DeviceAuthorizationRequestData;
use App\Data\DeviceCodeRequestData;
use App\Data\EnterDeviceCodePagePropsData;
use App\Data\OAuthAuthorizePagePropsData;
use App\Data\OAuthClientData;
use App\Data\OAuthRequestData;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * passport api
         * http://esbenp.github.io/2017/03/19/modern-rest-api-laravel-part-4/
         */
        // Passport::routes();
        // Passport::cookie(config('session.cookie') . '_token');

        if (app()->isProduction()) {
            Passport::ignoreRoutes();
        }

        Passport::authorizationView(function ($parameters) {
            return Inertia::render('oauth/authorize', new OAuthAuthorizePagePropsData(
                client: OAuthClientData::fromClient($parameters['client']),
                scopes: $parameters['scopes'],
                request: OAuthRequestData::fromPassportRequest($parameters['request']),
                authToken: $parameters['authToken'],
            ))->toResponse(request());
        });

        Passport::deviceUserCodeView(function ($parameters) {
            return Inertia::render('oauth/device', new EnterDeviceCodePagePropsData(
                request: DeviceCodeRequestData::fromPassportRequest($parameters['request']),
            ))->toResponse(request());
        });

        Passport::deviceAuthorizationView(function ($parameters) {
            return Inertia::render('oauth/device/authorize', new AuthorizeDevicePagePropsData(
                client: OAuthClientData::fromClient($parameters['client']),
                scopes: $parameters['scopes'],
                request: DeviceAuthorizationRequestData::fromPassportRequest($parameters['request']),
                authToken: $parameters['authToken'],
            ))->toResponse(request());
        });

        /**
         * Add `inertia` middleware to Passport routes that render as Inertia pages.
         * This enables `flash` data sharing for success/error states.
         */
        $this->app->booted(function () {
            $passportRouteNames = [
                'passport.authorizations.authorize',
                'passport.device.authorizations.authorize',
                'passport.device',
            ];

            foreach ($passportRouteNames as $routeName) {
                Route::getRoutes()->getByName($routeName)?->middleware(HandleInertiaRequests::class);
            }
        });

        /*
         * a general manage role
         * not used to actually determine any permissions other than to tell the ui to give access to management tools
         * specific management permissions are specified in specialized permissions (manageForums, manageNews, ...)
         * management roles specify that a user can interact with the given resource in _any_ way
         * which actions are allowed specifically has to be defined in the respective policies
         * Note: this ability should not be called 'manage' or policies might default to true if manage() method does not exist there
         */
        Gate::define('accessManagementTools', fn (User $user) => $user->hasAnyRole([
            Role::ROOT,
            Role::ADMINISTRATOR,
            Role::MODERATOR,
            Role::EVENT_MANAGER,
            Role::FORUM_MANAGER,
            Role::GAME_HASH_MANAGER,
            Role::NEWS_MANAGER,
            Role::RELEASE_MANAGER,
            Role::TICKET_MANAGER,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
            Role::ARTIST,
            Role::WRITER,
            Role::GAME_EDITOR,
        ]));

        /*
         * can "create". meant for creator tools opt-in
         */
        Gate::define('develop', fn (User $user) => $user->hasAnyRole([
            Role::DEVELOPER,
            Role::ARTIST,
            Role::WRITER,
        ]));

        /*
         * settings
         */
        Gate::define('updateSettings', function (?User $user, string $section = 'profile') {
            if (!$user) {
                return false;
            }

            $able = false;
            $able = match ($section) {
                'profile', 'site' => true,
                'library', 'notifications', 'privacy', 'account', 'social', 'applications', 'root' => $user->can('root'),
                default => $able,
            };

            return $able;
        });

        Gate::define('root', fn (User $user) => $user->hasAnyRole([Role::ROOT]));

        Gate::define('tool', function (User $user) {
            return app()->environment('local')
                || $user->hasAnyRole([Role::ROOT, Role::ADMINISTRATOR]);
        });

        Gate::define('viewLogViewer', fn (User $user) => $user->can('tool'));

        Gate::define('viewPulse', fn (User $user) => $user->can('tool'));

    }
}
