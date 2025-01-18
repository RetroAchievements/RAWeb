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
            ->selectRaw('MAX(created_at) as last_request, MAX(approved_at) as last_approval')
            ->first();

        // Users can only request a new username every 30 days.
        if ($lastChanges?->last_request && Carbon::parse($lastChanges->last_request)->isAfter(now()->subDays(30))) {
            return false;
        }

        // Users must wait 30 days after their last approved username change.
        if ($lastChanges?->last_approval && Carbon::parse($lastChanges->last_approval)->isAfter(now()->subDays(30))) {
            return false;
        }

        return true;
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

    public function resotre(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user): bool
    {
        return false;
    }
}
