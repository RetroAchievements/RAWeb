<?php

declare(strict_types=1);

namespace App\Platform\Policies;

use App\Models\Role;
use App\Models\User;
use App\Platform\Models\Achievement;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,

            /*
             * developers may at least upload new achievements to the server, create code notes, etc
             */
            // Role::DEVELOPER,

            /*
             * moderators may remove unfit content from achievements
             */
            // Role::MODERATOR,

            /*
             * artists may update achievement badges if the respective achievements are open for editing
             */
            // Role::ARTIST,

            /*
             * writers may update achievement title and description if the respective achievements are open for editing
             */
            // Role::WRITER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Achievement $achievement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
        ]);
    }

    public function update(User $user, Achievement $achievement): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,

            /*
             * moderators may remove unfit content from achievements
             */
            // Role::MODERATOR,

            /*
             * developers may at least upload new achievements to the server, create code notes, etc
             */
            // Role::DEVELOPER,

            /*
             * artists may update achievement badges if the respective achievements are open for editing
             */
            // Role::ARTIST,
        ]);
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        if ($achievement->is_published) {
            return false;
        }

        return $user->hasAnyRole([
            Role::HUB_MANAGER,
        ]);
    }

    public function restore(User $user, Achievement $achievement): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
        ]);
    }

    public function forceDelete(User $user, Achievement $achievement): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
        ]);
    }
}
