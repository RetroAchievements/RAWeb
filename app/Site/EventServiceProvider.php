<?php

declare(strict_types=1);

namespace App\Site;

use App\Platform\Events\SiteBadgeAwarded;
use App\Site\Events\UserDeleted;
use App\Site\Listeners\SendUserRegistrationNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        /*
         * framework
         */
        // Registered::class => [
        //     'App\Listeners\LogRegisteredUser',
        // ],
        // Attempting::class => [
        //     'App\Listeners\LogAuthenticationAttempt',
        // ],
        // Authenticated::class => [
        //     'App\Listeners\LogAuthenticated',
        // ],
        Login::class => [
            // UpdateUserTimezone::class,
        ],
        // Failed::class => [
        //     'App\Listeners\LogFailedLogin',
        // ],
        // Logout::class => [
        //     'App\Listeners\LogSuccessfulLogout',
        // ],
        // Lockout::class => [
        //     'App\Listeners\LogLockout',
        // ],
        // PasswordReset::class => [
        //     'App\Listeners\LogPasswordReset',
        // ],
        Registered::class => [
            // SendEmailVerificationNotification::class,
            SendUserRegistrationNotification::class,
        ],
        SiteBadgeAwarded::class => [
            // TODO Notify user
        ],
        UserDeleted::class => [
            // TODO Notify user/moderation
        ],
        Verified::class => [
            // UserVerifiedEmail::class,
        ],
    ];

    public function boot(): void
    {
        // User::observe(UserObserver::class);
    }
}
