<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PlayerAchievement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlayerAchievementPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user, ?User $player = null): bool
    {
        if ($user && $player && $user->is($player)) {
            return true;
        }

        /*
         * TODO: check user privacy settings
         */
        // $player->settings->games->public
        // return false;

        return true;
    }

    public function view(?User $user, PlayerAchievement $playerAchievement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // allowed to unlock
        return true;
    }

    public function update(User $user, PlayerAchievement $playerAchievement): bool
    {
        return false;
    }

    public function delete(User $user, PlayerAchievement $playerAchievement): bool
    {
        return false;
    }

    public function restore(User $user, PlayerAchievement $playerAchievement): bool
    {
        return false;
    }

    public function forceDelete(User $user, PlayerAchievement $playerAchievement): bool
    {
        return false;
    }

    public function manuallyAward(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MANUAL_UNLOCKER,
            Role::MODERATOR,
        ]);
    }
}
