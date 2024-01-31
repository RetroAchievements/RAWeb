<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Enums\Permissions;
use App\Models\Role;
use App\Models\User;
use App\Platform\Models\GameHash;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameHashPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ])
            || $user->getAttribute('Permissions') >= Permissions::Developer;
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
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ]);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
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
        return false;
    }
}
