<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GameAchievementSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameAchievementSetPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
            // Juniors can see set management, but can't manipulate sets.
            Role::DEVELOPER_JUNIOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, GameAchievementSet $gameAchievementSet): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Junior developers must seek approval to work on subsets, so
        // they are not included in this list.
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);
    }

    public function delete(User $user, ?GameAchievementSet $gameAchievementSet = null): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);
    }

    public function restore(User $user, GameAchievementSet $gameAchievementSet): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);
    }

    public function forceDelete(User $user, GameAchievementSet $gameAchievementSet): bool
    {
        return false;
    }
}
