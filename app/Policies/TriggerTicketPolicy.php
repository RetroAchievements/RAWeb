<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\TriggerTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;

class TriggerTicketPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::TICKET_MANAGER,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        // Guests cannot view tickets.
        if (!$user) {
            return false;
        }

        return true;
    }

    public function view(?User $user, TriggerTicket $triggerTicket): bool
    {
        // Guests cannot view tickets.
        if (!$user) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return
            $user->hasVerifiedEmail()
            && $user->created_at->diffInHours(Carbon::now(), true) >= 24
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

        if ($triggerable instanceof Leaderboard) {
            return $this->createLeaderboardTicket($user, $triggerable);
        }

        return $this->createAchievementTicket($user, $triggerable);
    }

    public function update(User $user, TriggerTicket $triggerTicket): bool
    {
        return false;
    }

    public function delete(User $user, TriggerTicket $triggerTicket): bool
    {
        return false;
    }

    public function restore(User $user, TriggerTicket $triggerTicket): bool
    {
        return false;
    }

    public function forceDelete(User $user, TriggerTicket $triggerTicket): bool
    {
        return false;
    }

    private function createAchievementTicket(User $user, Achievement $achievement): bool
    {
        // Users must have played the game to be able to create tickets for its achievements.
        return $user->hasPlayedGameForAchievement($achievement);
    }

    private function createLeaderboardTicket(User $user, Leaderboard $leaderboard): bool
    {
        // Users must have played the game to be able to create tickets for its leaderboards.
        // TODO $user->hasPlayedGameForLeaderboard ?
        return $user->hasPlayedGame($leaderboard->game);
    }

    public function updateState(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::TICKET_MANAGER,
        ]);
    }
}
