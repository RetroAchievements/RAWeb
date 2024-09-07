<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Permissions;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
            // Role::COMMUNITY_MANAGER, // rather a mix of moderator and specialized management role?
            Role::EVENT_MANAGER,
            Role::FORUM_MANAGER,
            Role::GAME_HASH_MANAGER,
            Role::NEWS_MANAGER,
            Role::RELEASE_MANAGER,
            Role::TICKET_MANAGER,
            Role::DEVELOPER,
            Role::ARTIST,
            Role::WRITER,
            Role::GAME_EDITOR,
        ])
            // TODO remove as soon as permission matrix is in place
            || $user->getAttribute('Permissions') >= Permissions::JuniorDeveloper);

        /*
         * can "create". meant for creator tools opt-in
         */
        Gate::define('develop', fn (User $user) => $user->hasAnyRole([
            Role::DEVELOPER,
            Role::ARTIST,
            Role::WRITER,
        ])
            // TODO remove as soon as permission matrix is in place
            || $user->getAttribute('Permissions') >= Permissions::JuniorDeveloper);

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

        Gate::define('viewBeta', fn (User $user) => $user->hasAnyRole([Role::BETA]));

        Gate::define('root', fn (User $user) => $user->hasAnyRole([Role::ROOT]));

        Gate::define('tool', function (User $user) {
            return app()->environment('local')
                || $user->hasAnyRole([Role::ROOT, Role::ADMINISTRATOR]);
        });

        Gate::define('viewLogViewer', fn (User $user) => $user->can('tool'));

        Gate::define('viewPulse', fn (User $user) => $user->can('tool'));

    }
}
