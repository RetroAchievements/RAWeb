<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\UserUsername;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserUsernamePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MODERATOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MODERATOR,
        ]);
    }

    public function create(User $user): bool
    {
        // Muted users cannot change their usernames.
        if ($user->isMuted()) {
            return false;
        }

        $lastChanges = UserUsername::whereUserId($user->id)
            ->selectRaw(<<<SQL
                MAX(created_at) as last_request, 
                MAX(approved_at) as last_approval,
                MAX(denied_at) as last_denial
            SQL)
            ->first();

        // If no previous requests exist, allow creation.
        if (
            !$lastChanges
            || (!$lastChanges->last_request && !$lastChanges->last_approval && !$lastChanges->last_denial)
        ) {
            return true;
        }

        // Check the most recent approval (90 day cooldown).
        $lastApproval = $lastChanges->last_approval ? Carbon::parse($lastChanges->last_approval) : null;
        if ($lastApproval && $lastApproval->isAfter(now()->subDays(90))) {
            return false;
        }

        // Check the most recent denial (30 day cooldown).
        $lastDenial = $lastChanges->last_denial ? Carbon::parse($lastChanges->last_denial) : null;
        if ($lastDenial && $lastDenial->isAfter(now()->subDays(30))) {
            return false;
        }

        // If neither approval nor denial is within their respective cooldown periods, allow creation.
        return true;
    }

    public function update(User $user): bool
    {
        return false;
    }

    public function delete(User $user): bool
    {
        // They'll need to wait for the cooldown period to lapse.
        return false;
    }

    public function restore(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
