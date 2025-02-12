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
                MAX(approved_at) as last_approval
            SQL)
            ->first();

        // If no previous requests exist, allow creation.
        if (!$lastChanges || (!$lastChanges->last_request && !$lastChanges->last_approval)) {
            return true;
        }

        // Get the most recent action date - between request and approval.
        $lastActionDate = max(
            $lastChanges->last_request ? Carbon::parse($lastChanges->last_request) : Carbon::create(0),
            $lastChanges->last_approval ? Carbon::parse($lastChanges->last_approval) : Carbon::create(0)
        );

        // Users get a 90-day cooldown after requests & after approval.
        return !$lastActionDate->isAfter(now()->subDays(90));
    }

    public function update(User $user): bool
    {
        return false;
    }

    public function delete(User $user): bool
    {
        // They'll just need to wait for 30 days to lapse.
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
