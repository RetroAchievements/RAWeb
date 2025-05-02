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
     * Inbox display_name => [Roles]
     */
    protected const INBOX_ROLES_MAP = [
        'DevCompliance' => [Role::DEV_COMPLIANCE],
        'QATeam' => [Role::QUALITY_ASSURANCE],
        'RAArtTeam' => [Role::ARTIST],
        'RACheats' => [Role::CHEAT_INVESTIGATOR],
        'RAdmin' => [Role::ADMINISTRATOR, Role::MODERATOR],
        'RAEvents' => [Role::EVENT_MANAGER],
        'WritingTeam' => [Role::WRITER],
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

            if (isset(static::INBOX_ROLES_MAP[$teamDisplayName])) {
                $allowedRoles = static::INBOX_ROLES_MAP[$teamDisplayName];
                foreach ($allowedRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
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

            if (isset(static::INBOX_ROLES_MAP[$teamDisplayName])) {
                $allowedRoles = static::INBOX_ROLES_MAP[$teamDisplayName];
                foreach ($allowedRoles as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
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

        foreach (static::INBOX_ROLES_MAP as $inboxDisplayName => $roles) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    $accessibleInboxes[] = $inboxDisplayName;

                    break;
                }
            }
        }

        return $accessibleInboxes;
    }
}
