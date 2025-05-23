<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\GameRelease;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameReleasePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_JUNIOR,
            Role::DEVELOPER,
            Role::GAME_EDITOR,
            Role::GAME_HASH_MANAGER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, GameRelease $gameRelease): bool
    {
        return true;
    }

    public function create(User $user, ?Game $game = null): bool
    {
        $canAlwaysCreate = $user->hasAnyRole([
            Role::DEVELOPER,
            Role::GAME_EDITOR,
            Role::GAME_HASH_MANAGER,
        ]);

        if ($canAlwaysCreate) {
            return true;
        }

        // If the user has a DEVELOPER_JUNIOR role, they need to have a claim
        // on the game or be the sole author of its achievements to be able to
        // update any of its metadata.
        if ($game && $user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return GamePolicy::canDeveloperJuniorUpdateGame($user, $game);
        }

        return false;
    }

    public function update(User $user, GameRelease $gameRelease): bool
    {
        $canAlwaysUpdate = $user->hasAnyRole([
            Role::DEVELOPER,
            Role::GAME_EDITOR,
            Role::GAME_HASH_MANAGER,
        ]);

        if ($canAlwaysUpdate) {
            return true;
        }

        // If the user has a DEVELOPER_JUNIOR role, they need to have a claim
        // on the game or be the sole author of its achievements to be able to
        // update any of its metadata.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            $gameRelease->loadMissing('game');

            return GamePolicy::canDeveloperJuniorUpdateGame($user, $gameRelease->game);
        }

        return false;
    }

    public function delete(User $user, GameRelease $gameRelease): bool
    {
        // The canonical title can be changed, but cannot be deleted.
        if ($gameRelease->is_canonical_game_title) {
            return false;
        }

        $canAlwaysDelete = $user->hasAnyRole([
            Role::DEVELOPER,
            Role::GAME_EDITOR,
            Role::GAME_HASH_MANAGER,
        ]);

        if ($canAlwaysDelete) {
            return true;
        }

        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            $gameRelease->loadMissing('game');

            return GamePolicy::canDeveloperJuniorUpdateGame($user, $gameRelease->game);
        }

        return false;
    }

    public function restore(User $user, GameRelease $gameRelease): bool
    {
        return false;
    }

    public function forceDelete(User $user, GameRelease $gameRelease): bool
    {
        return false;
    }
}
