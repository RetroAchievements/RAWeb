<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\GameHashSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameHashSetPolicy
{
    use HandlesAuthorization;

    public function manage(User $user, Game $game, GameHashSet $gameHashSet): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
        ]);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Game $game): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Game $game): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
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
