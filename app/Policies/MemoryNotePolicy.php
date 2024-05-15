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
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
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
        // TODO add rules for DEVELOPER_JUNIOR

        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ]);
    }

    public function update(User $user, MemoryNote $memoryNote): bool
    {
        if ($user->hasAnyRole([Role::DEVELOPER_STAFF, Role::DEVELOPER])) {
            return true;
        }

        // Users with the DEVELOPER_JUNIOR role are allowed to update their own notes.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR) && $user->is($memoryNote->user)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, MemoryNote $memoryNote): bool
    {
        if ($user->hasAnyRole([Role::DEVELOPER_STAFF, Role::DEVELOPER])) {
            return true;
        }

        // Users with the DEVELOPER_JUNIOR role are allowed to delete their own notes.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR) && $user->is($memoryNote->user)) {
            return true;
        }

        return false;
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
