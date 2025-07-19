<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GameSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameSetPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::GAME_EDITOR,

            Role::DEVELOPER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, GameSet $gameSet): bool
    {
        // TODO handle adult content hubs

        // If the hub has view role requirements, check them.
        if ($gameSet->has_view_role_requirement) {
            // If user is not authenticated, they cannot view the hub.
            if (!$user) {
                return false;
            }

            // Admins can always view hubs.
            if ($user->hasRole(Role::ADMINISTRATOR)) {
                return true;
            }

            $requiredRoleNames = $gameSet->viewRoles()->pluck('name')->toArray();

            return $user->hasAnyRole($requiredRoleNames);
        }

        // If no roles are required, anyone can view
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::GAME_HASH_MANAGER,
            Role::GAME_EDITOR,
        ]);
    }

    public function update(User $user, GameSet $gameSet): bool
    {
        // Admins can always edit hubs.
        if ($user->hasRole(Role::ADMINISTRATOR)) {
            return true;
        }

        // If the hub has update role requirements, check them.
        if ($gameSet->has_update_role_requirement) {
            $requiredRoleNames = $gameSet->updateRoles()->pluck('name')->toArray();

            return $user->hasAnyRole($requiredRoleNames);
        }

        // If no roles are required, use the default permissions.
        return $this->manage($user);
    }

    public function delete(User $user, GameSet $gameSet): bool
    {
        return false;
    }

    public function restore(User $user, GameSet $gameSet): bool
    {
        return false;
    }

    public function forceDelete(User $user, GameSet $gameSet): bool
    {
        return false;
    }

    public function toggleHasMatureContent(User $user, GameSet $gameSet): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
        ]);
    }

    public function manageRoleRequirements(User $user, ?GameSet $gameSet = null): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
        ]);
    }
}
