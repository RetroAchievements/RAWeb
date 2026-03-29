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
            Role::PLAYTEST_MANAGER,
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
            Role::PLAYTEST_MANAGER,
        ]);
    }

    public function update(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        return $this->canModifyCredit($user, $achievementSetAuthor);
    }

    public function delete(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        return $this->canModifyCredit($user, $achievementSetAuthor);
    }

    public function restore(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        return $this->canModifyCredit($user, $achievementSetAuthor);
    }

    public function forceDelete(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        return false;
    }

    private function canModifyCredit(User $user, AchievementSetAuthor $achievementSetAuthor): bool
    {
        // Developers can modify any credit.
        if ($user->hasRole(Role::DEVELOPER)) {
            return true;
        }

        // Artists can only modify artwork and banner credits.
        if ($user->hasRole(Role::ARTIST)) {
            return in_array($achievementSetAuthor->task, [
                AchievementSetAuthorTask::Artwork,
                AchievementSetAuthorTask::Banner,
            ], true);
        }

        // Playtest managers can only modify testing credits.
        if ($user->hasRole(Role::PLAYTEST_MANAGER)) {
            return $achievementSetAuthor->task === AchievementSetAuthorTask::Testing;
        }

        return false;
    }
}
