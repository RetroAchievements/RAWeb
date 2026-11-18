<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlayerGamePolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user, ?User $player = null): bool
    {
        if ($player && $user && $user->is($player)) {
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

    // TODO refactor to PlayerSessionPolicy
    public function viewSessionHistory(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MODERATOR,
            Role::CHEAT_INVESTIGATOR,
            Role::QUALITY_ASSURANCE,
        ]);
    }

    // TODO refactor to PlayerSessionPolicy
    public function viewActivity(User $user, User $player, Game $game): bool
    {
        if ($this->viewSessionHistory($user)) {
            return true;
        }

        return
            $user->can('viewHistory', Ticket::class)
            && Ticket::forGame($game)->where('reporter_id', $player->id)->exists();
    }
}
