<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Models\User;
use App\Platform\Models\Leaderboard;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaderboardPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Leaderboard $leaderboard): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Leaderboard $leaderboard): bool
    {
        return false;
    }

    public function delete(User $user, Leaderboard $leaderboard): bool
    {
        return false;
    }

    public function restore(User $user, Leaderboard $leaderboard): bool
    {
        return false;
    }

    public function forceDelete(User $user, Leaderboard $leaderboard): bool
    {
        return false;
    }
}
