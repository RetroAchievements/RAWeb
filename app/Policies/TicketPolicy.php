<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Exceptions\BannedUserException;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
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

    public function viewAny(?User $user, ?User $ticketsOwner = null): bool
    {
        // Guests cannot view tickets.
        if (!$user) {
            return false;
        }

        // Banned account subpages are hidden from the public. Tickets are the
        // exception, and only for people whose job it is to clean them up.
        if (
            $ticketsOwner?->isBanned()
            && !$user->hasAnyRole([Role::DEVELOPER, Role::MODERATOR, Role::ADMINISTRATOR])
        ) {
            throw new BannedUserException();
        }

        return true;
    }

    public function view(?User $user, Ticket $ticket): bool
    {
        // Guests cannot view tickets.
        if (!$user) {
            return false;
        }

        return true;
    }

    // Gives additional roles ability to view achievement logic on tickets and management page
    public function viewLogic(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MANUAL_UNLOCKER,
        ]);
    }

    // Gives additional roles ability to view player history on tickets, does not include compare unlocks view
    public function viewHistory(User $user): bool
    {
        return $user->hasAnyRole([
            Role::CHEAT_INVESTIGATOR,
            Role::MANUAL_UNLOCKER,
        ]);
    }

    public function create(User $user): bool
    {
        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        if ($user->created_at->diffInHours(Carbon::now(), true) < 24) {
            return false;
        }

        if ($user->is_muted || $user->banned_at) {
            return false;
        }

        // Untracked users are already generally known to have an unlock history
        // that is untrustworthy. Team members are exempt, as they already have an
        // elevated level of trust.
        if (
            $user->unranked_at !== null
            && $user->roles->whereNotIn('name', Role::nonTeamRoles())->isEmpty()
        ) {
            return false;
        }

        return true;
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

    public function update(User $user, Ticket $ticket): bool
    {
        return false;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return false;
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return false;
    }

    public function forceDelete(User $user, Ticket $ticket): bool
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

    public function updateState(User $user, Ticket $ticket, TicketAction $action): bool
    {
        // Don't write a comment for the same/current status.
        if ($action->targetState() === $ticket->state) {
            return false;
        }

        // Don't request from a user who is deleted.
        if ($action === TicketAction::Request && (!$ticket->reporter || $ticket->reporter->trashed())) {
            return false;
        }

        if ($user->hasAnyRole([Role::DEVELOPER, Role::MODERATOR, Role::ADMINISTRATOR])) {
            return true;
        }

        if ($user->id !== $ticket->reporter_id) {
            return false;
        }

        return match ($action) {
            TicketAction::ClosedMistaken => in_array(
                $ticket->state,
                [TicketState::Open, TicketState::Request, TicketState::Quarantined],
                true,
            ),
            TicketAction::Reopen => $ticket->state === TicketState::Request,
            default => false,
        };
    }
}
