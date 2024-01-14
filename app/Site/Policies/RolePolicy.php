<?php

declare(strict_types=1);

namespace App\Site\Policies;

use App\Site\Models\Role;
use App\Site\Models\User;
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
        return $user->assignableRoles->contains($model->name);
    }

    public function create(User $user): bool
    {
        // nobody creates roles just like that.
        return false;
    }

    public function update(User $user, Role $model): bool
    {
        return $user->assignableRoles->contains($model->name);
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
