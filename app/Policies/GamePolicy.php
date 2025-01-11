<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GamePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,

            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,

            Role::ARTIST,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Game $game): bool
    {
        // Age gates are handled at the UI level.

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            // Role::DEVELOPER,
        ]);
    }

    public function update(User $user, Game $game): bool
    {
        $canAlwaysUpdate = $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER,
        ]);

        if ($canAlwaysUpdate) {
            return true;
        }

        // If the user has a DEVELOPER_JUNIOR role, they need to have a claim
        // on the game or be the sole author of its achievements to be able to
        // update any of its metadata.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return $this->canDeveloperJuniorUpdateGame($user, $game);
        }

        return false;
    }

    public function delete(User $user, Game $game): bool
    {
        return false;
    }

    public function restore(User $user, Game $game): bool
    {
        return false;
    }

    public function forceDelete(User $user, Game $game): bool
    {
        return false;
    }

    public function updateField(User $user, Game $game, string $fieldName): bool
    {
        // Some roles can edit everything.
        if ($user->hasAnyRole([Role::ROOT, Role::GAME_HASH_MANAGER, Role::MODERATOR])) {
            return true;
        }

        $roleFieldPermissions = [
            // Junior Developers cannot edit the game title.
            Role::DEVELOPER_JUNIOR => ['GuideURL', 'Developer', 'Publisher', 'Genre', 'ImageIcon', 'ImageBoxArt', 'ImageTitle', 'ImageIngame', 'released_at', 'released_at_granularity'],

            Role::DEVELOPER => ['Title', 'GuideURL', 'Developer', 'Publisher', 'Genre', 'ImageIcon', 'ImageBoxArt', 'ImageTitle', 'ImageIngame', 'released_at', 'released_at_granularity'],
        ];

        $userRoles = $user->getRoleNames();

        // Aggregate the allowed fields for all roles the user has.
        $allowedFieldsForUser = collect($roleFieldPermissions)
            ->filter(function ($fields, $role) use ($userRoles) {
                return $userRoles->contains($role);
            })
            ->collapse()
            ->unique()
            ->all();

        // Junior Developers need to have a claim on the game if they want to edit game fields.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR) && !$this->canDeveloperJuniorUpdateGame($user, $game)) {
            return false;
        }

        // If any of the user's roles allow updating the specified field, return true.
        // Otherwise, they can't edit the field.
        return in_array($fieldName, $allowedFieldsForUser, true);
    }

    public function createForumTopic(User $user, Game $game): bool
    {
        if ($game->ForumTopicID) {
            return false;
        }

        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::FORUM_MANAGER,
            Role::MODERATOR,
        ]);
    }

    public function viewModifications(User $user): bool
    {
        return $this->manage($user);
    }

    public function manageContributionCredit(User $user, Game $game): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::ARTIST,
        ]);
    }

    public function viewDeveloperInterest(User $user, Game $game): bool
    {
        $hasActivePrimaryClaim = $user->loadMissing('achievementSetClaims')
            ->achievementSetClaims()
            ->whereGameId($game->id)
            ->primaryClaim()
            ->active()
            ->exists();

        // Devs and JrDevs can see the page, but they need to have an
        // active primary claim first. Collaborators for the game
        // cannot open the page.
        if ($hasActivePrimaryClaim) {
            return true;
        }

        // Mods and admins can see everything.
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MODERATOR,
        ]);
    }

    private function canDeveloperJuniorUpdateGame(User $user, Game $game): bool
    {
        // If the user has a DEVELOPER_JUNIOR role, they need to have a claim
        // on the game or be the sole author of its achievements to be able to
        // update any of the game's metadata.

        if ($user->hasActiveClaimOnGameId($game->id)) {
            return true;
        }

        $game->loadMissing('achievements.developer');

        return $game->achievements->every(function ($achievement) use ($user) {
            return $achievement->developer->is($user);
        });
    }
}
