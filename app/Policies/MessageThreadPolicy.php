<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use App\Policies\Concerns\HandlesTeamAccounts;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageThreadPolicy
{
    use HandlesAuthorization;
    use HandlesTeamAccounts;

    public function manage(User $user): bool
    {
        return false;
    }

    public function view(User $user, MessageThread $messageThread): bool
    {
        // Users can always read messages to themselves.
        $isDirectParticipant = $messageThread->participants()
            ->where('user_id', $user->id)
            ->exists();
        if ($isDirectParticipant) {
            return true;
        }

        // Get team accounts that this user can access through their attached roles.
        $accessibleTeamUserIds = $this->getAccessibleTeamIds($user);

        // Check if any of those team accounts are participants.
        return $messageThread->participants()
            ->whereIn('user_id', $accessibleTeamUserIds)
            ->exists();
    }

    public function viewAny(?User $user, ?User $teamAccount = null): bool
    {
        // Users are able to view inboxes of team accounts, assuming
        // the correct role is attached to the user.
        if ($user && $teamAccount) {
            return $this->canActAsTeamAccount($user, $teamAccount);
        }

        return true;
    }

    public function create(User $user, ?User $teamAccount = null): bool
    {
        // Users are able to create threads on behalf of team accounts,
        // assuming the correct role is attached to the user.
        if ($teamAccount) {
            return $this->canActAsTeamAccount($user, $teamAccount);
        }

        return true;
    }

    public function update(User $user, MessageThread $messageThread): bool
    {
        return false;
    }

    public function delete(User $user, MessageThread $messageThread): bool
    {
        $isParticipant = MessageThreadParticipant::where('thread_id', $messageThread->id)
            ->where('user_id', $user->id)
            ->exists();

        return $isParticipant;
    }

    public function restore(User $user, MessageThread $messageThread): bool
    {
        return false;
    }

    public function forceDelete(User $user, MessageThread $messageThread): bool
    {
        return false;
    }
}
