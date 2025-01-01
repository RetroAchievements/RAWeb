<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\TriggerTicket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TriggerTicketPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
        ]);
    }

    public function view(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return
            $user->hasVerifiedEmail()
            && $user->created_at->diffInDays() >= 1
            && !$user->is_muted
            && !$user->banned_at
        ;
    }

    // TODO `Model $triggerable` and check for `HasVersionedTrigger`
    public function createFor(User $user, Achievement|Leaderboard $triggerable): bool
    {
        if (!$this->create($user)) {
            return false;
        }

        if ($triggerable instanceof Achievement) {
            return $this->createAchievementTicket($user, $triggerable);
        }

        if ($triggerable instanceof Leaderboard) {
            return $this->createLeaderboardTicket($user, $triggerable);
        }
    }

    public function update(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    public function delete(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    public function restore(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    public function forceDelete(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    private function createAchievementTicket(User $user, Achievement $achievement): bool
    {
        /*
         * users must have played the game to be able to create tickets for its achievements
         */

        return $user->hasPlayed($achievement->game);
    }

    private function createLeaderboardTicket(User $user, Leaderboard $leaderboard): bool
    {
        /*
         * users must have played the game to be able to create tickets for its leaderboards
         */

        return $user->hasPlayed($leaderboard->game);
    }
}
