<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Achievement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,

            /*
             * developers may at least upload new achievements to the server, create code notes, etc
             */
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,

            /*
             * moderators may remove unfit content from achievements
             */
            // Role::MODERATOR,

            /*
             * artists may update achievement badges if the respective achievements are open for editing
             */
            // Role::ARTIST,

            /*
             * writers may update achievement title and description if the respective achievements are open for editing
             */
            Role::WRITER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Achievement $achievement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function update(User $user, Achievement $achievement): bool
    {
        // If the user has a DEVELOPER_JUNIOR role, they need to have a claim
        // on the game and the achievement must not be promoted to Core/Official.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return !$achievement->is_published && $user->hasActiveClaimOnGameId($achievement->game->id);
        }

        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,

            /*
             * moderators may remove unfit content from achievements
             */
            // Role::MODERATOR,

            /*
             * developers may at least upload new achievements to the server, create code notes, etc
             */
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,

            /*
             * artists may update achievement badges if the respective achievements are open for editing
             */
            // Role::ARTIST,

            /*
             * writers may update achievement title and description if the respective achievements are open for editing
             */
            Role::WRITER,
        ]);
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        if ($achievement->is_published) {
            return false;
        }

        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function restore(User $user, Achievement $achievement): bool
    {
        return $user->hasAnyRole([
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function forceDelete(User $user, Achievement $achievement): bool
    {
        return false;
    }

    public function updateField(User $user, ?Achievement $achievement, string $fieldName): bool
    {
        $roleFieldPermissions = [
            Role::DEVELOPER_JUNIOR => ['Title', 'Description', 'type', 'Points', 'DisplayOrder'],
            Role::DEVELOPER => ['Title', 'Description', 'Flags', 'type', 'Points', 'DisplayOrder'],
            Role::DEVELOPER_STAFF => ['Title', 'Description', 'Flags', 'type', 'Points', 'DisplayOrder'],
            Role::WRITER => ['Title', 'Description'],
        ];

        // Root can edit everything.
        if ($user->hasRole(Role::ROOT)) {
            return true;
        }

        $userRoles = $user->getRoleNames();

        // Aggregate the allowed fields for all roles the user has.
        $allowedFieldsForUser = collect($roleFieldPermissions)
            ->filter(function ($fields, $role) use ($userRoles) {
                return $userRoles->contains($role);
            })
            ->collapse()
            ->unique()
            ->all();

        // Junior Developers have additional specific criteria that must be satisfied
        // before they are allowed to edit achievement fields.
        if (
            isset($achievement)
            && $user->hasRole(Role::DEVELOPER_JUNIOR)
            && !$this->update($user, $achievement)
        ) {
            return false;
        }

        // If any of the user's roles allow updating the specified field, return true.
        // Otherwise, they can't edit the field.
        return in_array($fieldName, $allowedFieldsForUser, true);
    }
}
