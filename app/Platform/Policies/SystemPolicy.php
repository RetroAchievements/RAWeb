<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Platform\Models\System;
use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
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
        return false;
    }

    public function update(User $user, System $system): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
        ]);
    }

    public function delete(User $user, System $system): bool
    {
        return false;
    }

    public function restore(User $user, System $system): bool
    {
        return false;
    }

    public function forceDelete(User $user, System $system): bool
    {
        return false;
    }
}
