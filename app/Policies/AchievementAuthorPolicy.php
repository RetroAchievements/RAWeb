<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AchievementAuthor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementAuthorPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::MODERATOR,
            Role::TEAM_ACCOUNT,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, AchievementAuthor $achievementAuthor): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, AchievementAuthor $achievementAuthor): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, AchievementAuthor $achievementAuthor): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::MODERATOR,
            Role::TEAM_ACCOUNT,
        ]);
    }

    public function restore(User $user, AchievementAuthor $achievementAuthor): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::MODERATOR,
            Role::TEAM_ACCOUNT,
        ]);
    }

    public function forceDelete(User $user, AchievementAuthor $achievementAuthor): bool
    {
        return false;
    }
}
