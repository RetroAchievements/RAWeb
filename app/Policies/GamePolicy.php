<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions;
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
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Game $game): bool
    {
        /*
         * TODO: check age gate
         */

        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            // Role::DEVELOPER_STAFF,
            // Role::DEVELOPER,
        ]);
    }

    public function update(User $user, Game $game): bool
    {
        $canAlwaysUpdate = $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
        ]);

        if ($canAlwaysUpdate) {
            return true;
        }

        // If the user has a DEVELOPER_JUNIOR role, they need to have a claim
        // on the game or be the sole author of its achievements to be able to
        // update any of its metadata.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR) || $user->getAttribute('Permissions') === Permissions::JuniorDeveloper) {
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
            Role::DEVELOPER_JUNIOR => ['GuideURL', 'Developer', 'Publisher', 'Genre', 'released_at', 'released_at_granularity'],

            Role::DEVELOPER => ['Title', 'GuideURL', 'Developer', 'Publisher', 'Genre', 'released_at', 'released_at_granularity'],
            Role::DEVELOPER_STAFF => ['Title', 'sort_title', 'GuideURL', 'Developer', 'Publisher', 'Genre', 'released_at', 'released_at_granularity'],
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
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::FORUM_MANAGER,
            Role::MODERATOR,
        ]);
    }

    // TODO rename to viewActivitylog or use manage() ?
    public function viewModifications(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
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
