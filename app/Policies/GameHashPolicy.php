<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GameHash;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameHashPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, GameHash $gameHash): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
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

    public function delete(User $user, GameHash $gameHash): bool
    {
        return false;
    }

    public function restore(User $user, GameHash $gameHash): bool
    {
        return false;
    }

    public function forceDelete(User $user, GameHash $gameHash): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);
    }

    public function loadIncompatibleSet(User $user, GameHash $gameHash): bool
    {
        // Compatibility testers can always access the set they're testing.
        if ($user->is($gameHash->compatibilityTester)) {
            return true;
        }

        // QA members can always access all incompatible content.
        if ($user->hasRole(Role::QUALITY_ASSURANCE)) {
            return true;
        }

        // Achievement authors can always access their own work.
        return $gameHash->game->achievements()->where('user_id', $user->id)->exists();
    }
}
