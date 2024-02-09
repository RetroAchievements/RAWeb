<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeaderboardEntry;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaderboardEntryPolicy
{
    use HandlesAuthorization;

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
