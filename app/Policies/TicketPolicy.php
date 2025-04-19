<?php

declare(strict_types=1);

namespace App\Policies;

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

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        if ($user->created_at->diffInHours(Carbon::now(), true) < 24 || $user->is_muted || $user->banned_at) {
            return false;
        }

        return $user->playerSessions()->where('duration', '>', 5)->exists()
                || $user->playerGames()->where('time_taken', '>', 5)->exists();
    }

    public function updateState(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::TICKET_MANAGER,
        ]);
    }
}
