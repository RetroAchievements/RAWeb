<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Platform\Models\GameHash;
use App\Site\Enums\Permissions;
use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameHashPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->getAttribute('Permissions') >= Permissions::Developer;
        // return $user->hasAnyRole([
        //     Role::HUB_MANAGER,
        // ]);
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
            Role::HUB_MANAGER,
            Role::DEVELOPER_LEVEL_1,
            Role::DEVELOPER_LEVEL_2,
        ]);
    }

    public function update(User $user, GameHash $gameHash): bool
    {
        $hasCorrectRole = $user->hasAnyRole([
            Role::DEVELOPER_LEVEL_1,
            Role::DEVELOPER_LEVEL_2,
        ]);

        // TODO: Remove when permissions matrix is in place.
        $hasCorrectPermissions = $user->getAttribute('Permissions') >= Permissions::Developer;

        return $hasCorrectRole || $hasCorrectPermissions;
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
        return false;
    }
}
