<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Achievement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BadgePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            /*
             * developers may at least upload new achievements to the server, create code notes, etc
             */
            Role::DEVELOPER,

            /*
             * moderators may remove unfit content
             */
            Role::MODERATOR,

            /*
             * artists may update achievement badges if the respective achievements are open for editing
             */
            Role::ARTIST,
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
        return false;
    }

    public function update(User $user, Achievement $achievement): bool
    {
        return $user->hasAnyRole([
            /*
             * moderators may remove unfit content from achievements
             */
            // Role::ADMINISTRATOR,

            /*
             * moderators may remove unfit content from achievements
             */
            Role::MODERATOR,

            /*
             * developers may at least upload new achievements to the server, create code notes, etc
             */
            Role::DEVELOPER,

            /*
             * artists may update achievement badges if the respective achievements are open for editing
             */
            Role::ARTIST,
        ]);
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        return false;
    }

    public function restore(User $user, Achievement $achievement): bool
    {
        return false;
    }

    public function forceDelete(User $user, Achievement $achievement): bool
    {
        return false;
    }
}
