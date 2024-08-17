<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserGameListEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            Role::MODERATOR,
            Role::ADMINISTRATOR,
        ]);
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->is($targetUser) || $user->isFriendsWith($targetUser);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, UserGameListEntry $userGameListEntry): bool
    {
        return false;
    }

    public function delete(User $user, UserGameListEntry $userGameListEntry): bool
    {
        return false;
    }

    public function restore(User $user, UserGameListEntry $userGameListEntry): bool
    {
        return false;
    }

    public function forceDelete(User $user, UserGameListEntry $userGameListEntry): bool
    {
        return false;
    }
}
