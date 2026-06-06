<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PlayerBadge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlayerBadgePolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user, ?User $player = null): bool
    {
        if ($user && $player && $user->is($player)) {
            return true;
        }

        return true;
    }

    public function view(?User $user, PlayerBadge $playerBadge): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PlayerBadge $playerBadge): bool
    {
        return false;
    }

    public function delete(User $user, PlayerBadge $playerBadge): bool
    {
        return false;
    }

    public function restore(User $user, PlayerBadge $playerBadge): bool
    {
        return false;
    }

    public function forceDelete(User $user, PlayerBadge $playerBadge): bool
    {
        return false;
    }
}
