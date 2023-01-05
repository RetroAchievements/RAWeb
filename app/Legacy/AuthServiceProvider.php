<?php

declare(strict_types=1);

namespace App\Legacy;

use App\Legacy\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use RA\Permissions;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        /*
         * Account Policies
         */
        // User::class => UserPolicy::class,
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
