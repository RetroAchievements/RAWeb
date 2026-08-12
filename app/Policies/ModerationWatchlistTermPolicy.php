<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ModerationWatchlistTerm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ModerationWatchlistTermPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function view(User $user, ModerationWatchlistTerm $moderationWatchlistTerm): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function update(User $user, ModerationWatchlistTerm $moderationWatchlistTerm): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function delete(User $user, ModerationWatchlistTerm $moderationWatchlistTerm): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function restore(User $user, ModerationWatchlistTerm $moderationWatchlistTerm): bool
    {
        return false;
    }

    public function forceDelete(User $user, ModerationWatchlistTerm $moderationWatchlistTerm): bool
    {
        return false;
    }
}
