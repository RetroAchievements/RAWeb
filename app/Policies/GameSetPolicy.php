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

        return true;
    }

    public function create(User $user): bool
    {
        return false;

        // Temporarily disabled.
        // If an avatar or card component renders for a hub that has no backing game,
        // a server error is thrown. Can be re-enabled after it's verified that [hub] shortcodes
        // and game avatar components (ie: on the game page) pointing to hubs don't throw any
        // errors if a backing game does not exist.

        // return $user->hasAnyRole([
        //     Role::ADMINISTRATOR,
        //     Role::GAME_HASH_MANAGER,
        //     Role::GAME_EDITOR,
        // ]);
    }

    public function update(User $user, GameSet $gameSet): bool
    {
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
}
