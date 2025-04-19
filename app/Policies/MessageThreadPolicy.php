<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageThreadPolicy
{
    use HandlesAuthorization;

    /**
     * Role => display_name
     */
    protected const ROLE_INBOX_MAP = [
        Role::ADMINISTRATOR => 'RAdmin',
        Role::DEV_COMPLIANCE => 'DevCompliance',
        Role::QUALITY_ASSURANCE => 'QATeam',
        Role::ARTIST => 'RAArtTeam',
        Role::CHEAT_INVESTIGATOR => 'RACheats',
        Role::WRITER => 'WritingTeam',
        Role::EVENT_MANAGER => 'RAEvents',
    ];

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
            $teamDisplayName = $teamAccount->display_name;

            foreach (static::ROLE_INBOX_MAP as $role => $inboxDisplayName) {
                if ($inboxDisplayName === $teamDisplayName && $user->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function create(User $user, ?User $teamAccount = null): bool
    {
        // Users are able to create threads on behalf of team accounts,
        // assuming the correct role is attached to the user.
        if ($teamAccount) {
            $teamDisplayName = $teamAccount->display_name;

            foreach (static::ROLE_INBOX_MAP as $role => $inboxDisplayName) {
                if ($inboxDisplayName === $teamDisplayName && $user->hasRole($role)) {
                    return true;
                }
            }

            return false;
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

    /**
     * Get user IDs of team accounts that this user can access.
     */
    public function getAccessibleTeamIds(User $user): array
    {
        $accessibleInboxes = $this->getAccessibleTeamInboxes($user);

        if (empty($accessibleInboxes)) {
            return [];
        }

        return User::whereIn('User', $accessibleInboxes)
            ->pluck('ID')
            ->toArray();
    }

    /**
     * Get all team inboxes this user can access.
     */
    public function getAccessibleTeamInboxes(User $user): array
    {
        $accessibleInboxes = [];

        foreach (static::ROLE_INBOX_MAP as $role => $inboxDisplayName) {
            if ($user->hasRole($role)) {
                $accessibleInboxes[] = $inboxDisplayName;
            }
        }

        return $accessibleInboxes;
    }
}
