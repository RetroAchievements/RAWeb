<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlayerGamePolicy
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
        // $player->settings->games->public
        // return false;

        return true;
    }

    public function view(?User $user, PlayerGame $playerGame): bool
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

    public function update(User $user, PlayerGame $playerGame): bool
    {
        return false;
    }

    public function delete(User $user, PlayerGame $playerGame): bool
    {
        return false;
    }

    public function restore(User $user, PlayerGame $playerGame): bool
    {
        return false;
    }

    public function forceDelete(User $user, PlayerGame $playerGame): bool
    {
        return false;
    }

    public function viewSessionHistory(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
            Role::CHEAT_INVESTIGATOR,
        ])
            || $user->getAttribute('Permissions') >= Permissions::Moderator;
    }
}
