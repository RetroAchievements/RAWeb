<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AchievementAuthor;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\AchievementAuthorTask;
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
            Role::ARTIST,
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
        return $this->canUpsertTask($user, AchievementAuthorTask::tryFrom($achievementAuthor->task));
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

    public function canUpsertTask(User $user, AchievementAuthorTask $task): bool
    {
        // These roles can assign any type of credit.
        $alwaysAllowed = [
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::MODERATOR,
            Role::TEAM_ACCOUNT,
        ];
        if ($user->hasAnyRole($alwaysAllowed)) {
            return true;
        }

        // Artists can assign artwork credit.
        if ($task === AchievementAuthorTask::Artwork && $user->hasRole(Role::ARTIST)) {
            return true;
        }

        return false;
    }
}
