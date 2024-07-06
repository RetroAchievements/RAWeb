<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeaderboardEntry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaderboardEntryPolicy
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

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, LeaderboardEntry $leaderboardEntry): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        // allowed to submit a score
        return true;
    }

    public function update(User $user, LeaderboardEntry $leaderboardEntry): bool
    {
        return false;
    }

    public function delete(User $user, LeaderboardEntry $leaderboardEntry): bool
    {
        $canAlwaysDelete = [
            Role::ROOT,
            Role::ADMINISTRATOR,
            Role::MODERATOR,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ];

        if ($user->hasAnyRole($canAlwaysDelete)) {
            return true;
        }

        // Junior Developers can only delete their own entries.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR) && $leaderboardEntry->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function restore(User $user, LeaderboardEntry $leaderboardEntry): bool
    {
        return false;
    }

    public function forceDelete(User $user, LeaderboardEntry $leaderboardEntry): bool
    {
        return false;
    }
}
