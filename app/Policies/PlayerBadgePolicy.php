<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PlayerBadge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlayerBadgePolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user, User $player): bool
    {
        if ($user && $user->is($player)) {
            return true;
        }

        /*
         * TODO: check user privacy settings
         */
        // $player->settings->badges->public
        // return false;

        return true;
    }

    public function view(?User $user, PlayerBadge $userBadge): bool
    {
        if (!$user) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PlayerBadge $userBadge): bool
    {
        return false;
    }

    public function delete(User $user, PlayerBadge $userBadge): bool
    {
        return false;
    }

    public function restore(User $user, PlayerBadge $userBadge): bool
    {
        return false;
    }

    public function forceDelete(User $user, PlayerBadge $userBadge): bool
    {
        return false;
    }
}
