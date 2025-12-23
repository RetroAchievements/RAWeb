<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AchievementSetAuthor;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\AchievementSetAuthorTask;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementSetAuthorPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
            Role::ARTIST,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::ARTIST,
        ]);
    }

    public function update(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        // Developers can update any credit.
        if ($user->hasRole(Role::DEVELOPER)) {
            return true;
        }

        // Artists can only update artwork credits.
        if ($user->hasRole(Role::ARTIST)) {
            return $achievementSetAuthor->task === AchievementSetAuthorTask::Artwork;
        }

        return false;
    }

    public function delete(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        // Developers can delete any credit.
        if ($user->hasRole(Role::DEVELOPER)) {
            return true;
        }

        // Artists can only delete artwork credits.
        if ($user->hasRole(Role::ARTIST)) {
            return $achievementSetAuthor->task === AchievementSetAuthorTask::Artwork;
        }

        return false;
    }

    public function restore(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        // Developers can restore any credit.
        if ($user->hasRole(Role::DEVELOPER)) {
            return true;
        }

        // Artists can only restore artwork credits.
        if ($user->hasRole(Role::ARTIST)) {
            return $achievementSetAuthor->task === AchievementSetAuthorTask::Artwork;
        }

        return false;
    }

    public function forceDelete(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        return false;
    }
}
