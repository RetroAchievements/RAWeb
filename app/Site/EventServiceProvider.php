<?php

declare(strict_types=1);

namespace App\Site;

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
            // Upd eUserTimezone::class,
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
        Verified::class => [
            // UserVerifiedEmail::class,
        ],
    ];

    public function boot(): void
    {
        // User::observe(UserObserver::class);
    }
}
