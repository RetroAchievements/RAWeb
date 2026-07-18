<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GameBadge;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameBadgePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::ARTIST,
            Role::MODERATOR,
        ]);
    }

    public function delete(User $user, GameBadge $gameBadge): bool
    {
        return $this->manage($user);
    }

    public function restore(User $user, GameBadge $gameBadge): bool
    {
        return $this->manage($user);
    }
}
