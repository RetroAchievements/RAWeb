<?php

namespace App\Policies;

use App\Models\AchievementSetVersion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementSetVersionPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, AchievementSetVersion $achievementSetVersion): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
    }

    public function update(User $user, AchievementSetVersion $achievementSetVersion): bool
    {
    }

    public function delete(User $user, AchievementSetVersion $achievementSetVersion): bool
    {
    }

    public function restore(User $user, AchievementSetVersion $achievementSetVersion): bool
    {
    }

    public function forceDelete(User $user, AchievementSetVersion $achievementSetVersion): bool
    {
    }
}
