<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Models\Role;
use App\Models\User;
use App\Platform\Models\EmulatorRelease;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmulatorReleasePolicy
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
            Role::RELEASE_MANAGER,
        ]);
    }

    public function view(User $user, EmulatorRelease $emulatorRelease): bool
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

    public function update(User $user, EmulatorRelease $emulatorRelease): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function delete(User $user, EmulatorRelease $emulatorRelease): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function restore(User $user, EmulatorRelease $emulatorRelease): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function forceDelete(User $user, EmulatorRelease $emulatorRelease): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }
}
