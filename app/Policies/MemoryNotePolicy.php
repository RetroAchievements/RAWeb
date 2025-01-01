<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MemoryNote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MemoryNotePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_JUNIOR,
            Role::DEVELOPER,
        ]);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MemoryNote $memoryNote): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_JUNIOR,
            Role::DEVELOPER,
        ]);
    }

    public function update(User $user, MemoryNote $memoryNote): bool
    {
        // If the user has a DEVELOPER_JUNIOR role, they need to have authored the note to edit it.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR) && $user->is($memoryNote->user)) {
            return true;
        }

        return $user->hasAnyRole([
            Role::DEVELOPER,
        ]);
    }

    public function delete(User $user, MemoryNote $memoryNote): bool
    {
        // If the user has a DEVELOPER_JUNIOR role, they need to have authored the note to delete it.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR) && $user->is($memoryNote->user)) {
            return true;
        }

        return $user->hasAnyRole([
            Role::DEVELOPER,
        ]);
    }

    public function restore(User $user, MemoryNote $memoryNote): bool
    {
        return false;
    }

    public function forceDelete(User $user, MemoryNote $memoryNote): bool
    {
        return false;
    }
}
