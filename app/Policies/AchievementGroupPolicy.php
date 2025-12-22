<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AchievementGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementGroupPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::DEV_COMPLIANCE,
            Role::GAME_EDITOR,
            Role::QUALITY_ASSURANCE,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, AchievementGroup $achievementGroup): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, ?AchievementGroup $achievementGroup = null): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, ?AchievementGroup $achievementGroup = null): bool
    {
        return $this->manage($user);
    }

    public function restore(User $user, AchievementGroup $achievementGroup): bool
    {
        return $this->manage($user);
    }

    public function forceDelete(User $user, AchievementGroup $achievementGroup): bool
    {
        return false;
    }
}
