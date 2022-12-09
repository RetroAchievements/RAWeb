<?php

declare(strict_types=1);

namespace LegacyApp\Site;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;
use LegacyApp\Site\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function register()
    {
    }

    public function boot(): void
    {
        $this->registerPolicies();

        /*
         * don't drink and root
         */
        Gate::before(
            function (Authenticatable $user, $ability) {
                if ($user instanceof User && $user->Permissions >= Permissions::Admin) {
                    return true;
                }

                /*
                 * If the callback returns a non-null result that result will be considered the result of a positive check.
                 */
                // don't: return null;
            }
        );
    }
}
