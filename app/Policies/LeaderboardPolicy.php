<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeaderboardPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Leaderboard $leaderboard): bool
    {
        return true;
    }

    public function create(User $user, ?Game $game = null): bool
    {
        if ($game && $user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return $user->hasActiveClaimOnGameId($game->id);
        }

        return $user->hasAnyRole([
            Role::DEVELOPER,
        ]);
    }

    public function update(User $user, Leaderboard $leaderboard): bool
    {
        $canEditAnyLeaderboard = $user->hasAnyRole([
            /*
             * developers can upload/edit any leaderboard
             */
            Role::DEVELOPER,

            /*
             * writers may update leaderboard title and description if the respective leaderboard are open for editing
             */
            Role::WRITER,
        ]);

        if ($canEditAnyLeaderboard) {
            return true;
        }

        // Junior Developers have additional specific criteria that must be satisfied
        // before they are allowed to edit leaderboard fields.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return $user->is($leaderboard->developer);
        }

        return false;
    }

    public function updateField(User $user, ?Leaderboard $leaderboard, string $fieldName): bool
    {
        $roleFieldPermissions = [
            Role::DEVELOPER_JUNIOR => ['Title', 'Description', 'Format', 'LowerIsBetter', 'DisplayOrder'],
            Role::DEVELOPER => ['Title', 'Description', 'Format', 'LowerIsBetter', 'DisplayOrder'],
            Role::WRITER => ['Title', 'Description'],
        ];

        // Root can edit everything.
        if ($user->hasRole(Role::ROOT)) {
            return true;
        }

        $userRoles = $user->getRoleNames();

        // Aggregate the allowed fields for all roles the user has.
        $allowedFieldsForUser = collect($roleFieldPermissions)
            ->filter(function ($fields, $role) use ($userRoles, $user, $leaderboard) {
                if (!$userRoles->contains($role)) {
                    return false;
                }

                // Junior Developers have additional specific criteria that must be satisfied
                // before they are allowed to edit leaderboard fields.
                if ($role === Role::DEVELOPER_JUNIOR) {
                    return isset($leaderboard) && $this->juniorDeveloperCanUpdate($user, $leaderboard);
                }

                return true;
            })
            ->collapse()
            ->unique()
            ->all();

        // If any of the user's roles allow updating the specified field, return true.
        // Otherwise, they can't edit the field.
        return in_array($fieldName, $allowedFieldsForUser, true);
    }

    private function juniorDeveloperCanUpdate(User $user, Leaderboard $leaderboard): bool
    {
        // If the user has a DEVELOPER_JUNIOR role, they need to have a claim on the game
        return $user->hasActiveClaimOnGameId($leaderboard->game->id);
    }

    public function delete(User $user, Leaderboard $leaderboard): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
        ]);
    }

    public function restore(User $user, Leaderboard $leaderboard): bool
    {
        return false;
    }

    public function forceDelete(User $user, Leaderboard $leaderboard): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
        ]);
    }

    public function resetAllEntries(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
        ]);
    }
}
