<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemPolicy
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

    public function view(?User $user, System $system): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function update(User $user, System $system): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function delete(User $user, System $system): bool
    {
        if ($system->active) {
            return false;
        }

        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function restore(User $user, System $system): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function forceDelete(User $user, System $system): bool
    {
        return false;
    }
}
