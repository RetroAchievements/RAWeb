<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Platform;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlatformPolicy
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

    public function view(User $user, Platform $platform): bool
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

    public function update(User $user, Platform $platform): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function delete(User $user, Platform $platform): bool
    {
        return false;
    }

    public function restore(User $user, Platform $platform): bool
    {
        return false;
    }

    public function forceDelete(User $user, Platform $platform): bool
    {
        return false;
    }
}
