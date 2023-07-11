<?php

declare(strict_types=1);

namespace App\Site\Policies;

use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, User $model): bool
    {
        /*
         * users may always view themselves
         */
        if ($user && $user->is($model)) {
            return true;
        }

        /*
         * TODO: check user's privacy settings
         */
        return true;
    }

    public function create(User $user): bool
    {
        // nobody creates users just like that.
        return false;
    }

    public function update(User $user, User $model): bool
    {
        /*
         * Note: this is not related to implicit authorization for user settings but for moderation purposes
         */
        return $this->requireAdministrativePrivileges($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->requireAdministrativePrivileges($user, $model);
    }

    public function restore(User $user, User $model): bool
    {
        return $this->requireAdministrativePrivileges($user, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        // nobody, yet
        return false;
    }

    public function ban(User $user, User $mode): bool
    {
        return $user->banned_at === null;
    }

    public function unban(User $user, User $mode): bool
    {
        return $user->banned_at !== null;
    }

    public function mute(User $user, User $mode): bool
    {
        return $user->muted_until === null;
    }

    public function unmute(User $user, User $mode): bool
    {
        return $user->muted_until !== null;
    }

    public function track(User $user, User $mode): bool
    {
        return $user->unranked_at !== null;
    }

    public function unrank(User $user, User $mode): bool
    {
        return $user->unranked_at === null;
    }

    public function viewFriends(User $user, User $model): bool
    {
        if (!$user->is($model)) {
            return false;
        }

        /*
         * TODO: check privacy settings
         */

        return true;
    }

    public function updateProfileSettings(User $user, User $model): bool
    {
        /*
         * users may only edit their own settings
         * kept here for settings button on profiles
         */
        if (!$user->is($model)) {
            return false;
        }

        return true;
    }

    public function deleteAvatar(User $user, User $model): bool
    {
        /*
         * users may delete their own avatar
         */
        if ($user->is($model)) {
            return true;
        }

        return $this->requireAdministrativePrivileges($user, $model);
    }

    public function deleteMotto(User $user, User $model): bool
    {
        /*
         * users may delete their own avatar
         */
        if ($user->is($model)) {
            return true;
        }

        return $this->requireAdministrativePrivileges($user, $model);
    }

    private function requireAdministrativePrivileges(User $user, User $model): bool
    {
        /*
         * users may not delete themselves
         */
        if ($user->is($model)) {
            return false;
        }

        /*
         * admins may not delete other admins
         */
        // if ($user->hasRole(Role::ADMINISTRATOR)) {
        //     if ($model->hasRole(Role::ADMINISTRATOR)) {
        //         return false;
        //     }
        // }

        /*
         * moderators may not delete admins or other moderators
         */
        // if ($user->hasRole(Role::MODERATOR)) {
        //     if ($model->hasAnyRole([Role::ADMINISTRATOR, Role::MODERATOR])) {
        //         return false;
        //     }
        // }

        // return $user->hasAnyRole([
        //     Role::ADMINISTRATOR,
        //     Role::MODERATOR,
        // ]);

        return false;
    }
}
