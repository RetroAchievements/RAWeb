<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AchievementSet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementSetPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, AchievementSet $achievementSet): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function delete(User $user, ?AchievementSet $achievementSet = null): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function restore(User $user, AchievementSet $achievementSet): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function forceDelete(User $user, AchievementSet $achievementSet): bool
    {
        return false;
    }

    public function markGameHashAsIncompatible(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }
}
