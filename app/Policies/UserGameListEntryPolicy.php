<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserGameListEntryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, UserGameListEntry $userGameListEntry): bool
    {
        return false;
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
