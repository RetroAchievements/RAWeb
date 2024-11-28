<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AchievementSetAuthor;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementSetAuthorPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
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
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::ARTIST,
        ]);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::ARTIST,
        ]);
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::ARTIST,
        ]);
    }

    public function restore(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::ARTIST,
        ]);
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
