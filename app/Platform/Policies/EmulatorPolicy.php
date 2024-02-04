<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Models\Role;
use App\Models\User;
use App\Platform\Models\Emulator;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmulatorPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            Role::ROOT,
            Role::RELEASE_MANAGER,
        ]);
    }

    public function view(User $user, Emulator $emulator): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function update(User $user, Emulator $emulator): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function delete(User $user, Emulator $emulator): bool
    {
        return false;
    }

    public function restore(User $user, Emulator $emulator): bool
    {
        return false;
    }

    public function forceDelete(User $user, Emulator $emulator): bool
    {
        return false;
    }
}
