<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\Rank;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role as SpatieRole;

class UserPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $this->requireAdministrativePrivileges($user);
    }

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
        if ($user->is($model)) {
            return true;
        }

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

    public function updateRoles(User $user, User $model): bool
    {
        // admins may update roles of non-root admins
        if (
            $user->hasRole(Role::ADMINISTRATOR)
            && !$model->hasAnyRole(Role::ROOT)
        ) {
            return true;
        }

        // users may attach any role to themselves
        if ($model->is($user)) {
            return true;
        }

        return $this->requireAdministrativePrivileges($user, $model);
    }

    public function attachRole(User $user, User $model, SpatieRole $role): bool
    {
        if (!$this->requireAdministrativePrivileges($user, $model)) {
            return false;
        }

        return $this->assignableRoles->contains($role->name);
    }

    public function detachRole(User $user, User $model, SpatieRole $role): bool
    {
        // users may detach any role from themselves
        if ($model->is($user)) {
            return true;
        }

        // admins may update roles of non-root admins
        if (
            $user->hasRole(Role::ADMINISTRATOR)
            && !$model->hasAnyRole(Role::ROOT)
        ) {
            return $user->assignableRoles->contains($role->name);
        }

        if (!$this->requireAdministrativePrivileges($user, $model)) {
            return false;
        }

        // make sure only roles are detachable that the request user is allowed to
        return $user->assignableRoles->contains($role->name);
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

    public function updateAvatar(User $user): bool
    {
        // Users may only upload a new avatar if they have been a member for at
        // least 14 days or if they have earned at least a minimum number of points
        // in either mode.

        if ($user->isMuted()) {
            return false;
        }

        if ($user->points >= Rank::MIN_POINTS || $user->points_softcore >= Rank::MIN_POINTS) {
            return true;
        }

        $membershipDuration = now()->diffInDays($user->created_at ?? now());
        if ($membershipDuration >= 14) {
            return true;
        }

        return false;
    }

    public function deleteAvatar(User $user, User $model): bool
    {
        // users may delete their own avatar
        if ($user->is($model)) {
            return true;
        }

        return $this->requireAdministrativePrivileges($user, $model);
    }

    public function deleteMotto(User $user, User $model): bool
    {
        // users may delete their own avatar
        if ($user->is($model)) {
            return true;
        }

        return $this->requireAdministrativePrivileges($user, $model);
    }

    private function requireAdministrativePrivileges(User $user, ?User $model = null): bool
    {
        if (!$model) {
            return $user->hasAnyRole([
                Role::ROOT,
                Role::ADMINISTRATOR,
                Role::MODERATOR,
            ]);
        }

        /*
         * users may not manage themselves
         */
        if ($user->is($model)) {
            return false;
        }

        /*
         * root may not manage other root
         */
        if ($user->hasRole(Role::ROOT)) {
            if ($model->hasAnyRole(Role::ROOT)) {
                return false;
            }

            return true;
        }

        /*
         * admins may not manage other admins
         */
        if ($user->hasRole(Role::ADMINISTRATOR)) {
            if ($model->hasAnyRole(Role::ROOT, Role::ADMINISTRATOR)) {
                return false;
            }
        }

        /*
         * moderators may not delete root, admins, or other moderators
         */
        if ($user->hasRole(Role::MODERATOR)) {
            if ($model->hasAnyRole([Role::ROOT, Role::ADMINISTRATOR, Role::MODERATOR])) {
                return false;
            }
        }

        return $user->hasAnyRole([
            Role::ROOT,
            Role::ADMINISTRATOR,
            Role::MODERATOR,
        ]);
    }
}
