<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ROOT,
            Role::ADMINISTRATOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return false;
    }

    public function view(?User $user, Role $model): bool
    {
        return $user->hasAnyRole([
            Role::ROOT,
            Role::ADMINISTRATOR,
        ]);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Role $model): bool
    {
        return false;
    }

    public function delete(User $user, Role $model): bool
    {
        return false;
    }

    public function restore(User $user, Role $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, Role $model): bool
    {
        return false;
    }
}
