<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaderboardPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Leaderboard $leaderboard): bool
    {
        return true;
    }

    public function create(User $user, ?Game $game = null): bool
    {
        if ($game && $user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return $user->hasActiveClaimOnGameId($game->id);
        }

        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ]);
    }

    public function update(User $user, Leaderboard $leaderboard): bool
    {
        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return $user->is($leaderboard->developer);
        }

        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ]);
    }

    public function delete(User $user, Leaderboard $leaderboard): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ]);
    }

    public function restore(User $user, Leaderboard $leaderboard): bool
    {
        return false;
    }

    public function forceDelete(User $user, Leaderboard $leaderboard): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ]);
    }

    public function resetAllEntries(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ]);
    }
}
