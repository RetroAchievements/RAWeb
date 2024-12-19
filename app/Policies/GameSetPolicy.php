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

            Role::DEVELOPER_STAFF,
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

        // TODO enable after dropping GameAlternatives
        // return $user->hasAnyRole([
        //     Role::ADMINISTRATOR,
        //     Role::DEVELOPER_STAFF,
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
}
