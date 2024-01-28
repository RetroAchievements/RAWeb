<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Platform\Models\Game;
use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GamePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
            // Role::DEVELOPER_STAFF,
            // Role::DEVELOPER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Game $game): bool
    {
        /*
         * TODO: check age gate
         */

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
            // Role::DEVELOPER_STAFF,
            // Role::DEVELOPER,
        ]);
    }

    public function update(User $user, Game $game): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
            // Role::DEVELOPER_STAFF,
            // Role::DEVELOPER,
        ]);
    }

    public function delete(User $user, Game $game): bool
    {
        return false;
    }

    public function restore(User $user, Game $game): bool
    {
        return false;
    }

    public function forceDelete(User $user, Game $game): bool
    {
        return false;
    }
}
