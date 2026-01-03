<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;

trait HandlesTeamAccounts
{
    /**
     * Check if a user can act on behalf of a team account.
     */
    public function canActAsTeamAccount(User $user, User $teamAccount): bool
    {
        $teamUsername = $teamAccount->username;
        $teamAccountsConfig = config('teams.accounts', []);

        if (!isset($teamAccountsConfig[$teamUsername])) {
            return false;
        }

        $allowedRoles = $teamAccountsConfig[$teamUsername];
        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user IDs of team accounts that this user can access.
     */
    public function getAccessibleTeamIds(User $user): array
    {
        $accessibleTeamUsernames = $this->getAccessibleTeamUsernames($user);

        if (empty($accessibleTeamUsernames)) {
            return [];
        }

        return User::whereIn('username', $accessibleTeamUsernames)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get all team usernames this user can access.
     */
    public function getAccessibleTeamUsernames(User $user): array
    {
        $userRoles = $user->roles()->pluck('name')->toArray();
        $accessibleUsernames = [];
        $teamAccountsConfig = config('teams.accounts', []);

        foreach ($teamAccountsConfig as $teamUsername => $roles) {
            if (count(array_intersect($userRoles, $roles)) > 0) {
                $accessibleUsernames[] = $teamUsername;
            }
        }

        return $accessibleUsernames;
    }

    /**
     * Determine if the sentBy information should be included for a message/comment.
     * Only include if the author is a team account the user has access to.
     * This prevents us from accidentally leaking the data in Inertia's page props.
     */
    public function shouldIncludeSentByValue(User $user, int $authorId, ?int $sentById): bool
    {
        // No sentBy value to include.
        if ($sentById === null) {
            return false;
        }

        // Check if the author is a team account the user has access to.
        $accessibleTeamIds = $this->getAccessibleTeamIds($user);

        return in_array($authorId, $accessibleTeamIds, true);
    }
}
