<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\GameComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameCommentPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function viewAny(?User $user, Game $commentable): bool
    {
        /*
         * check guests first
         */
        if (!$user) {
            return true;
        }

        if ($user->hasAnyRole([
            Role::MODERATOR,
        ])) {
            return true;
        }

        return true;
    }

    public function view(User $user, GameComment $comment): bool
    {
        return true;
    }

    public function create(?User $user, ?Game $commentable): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->is_muted || $user->isBanned()) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        if ($user->isFreshAccount()) {
            return false;
        }

        // Handle if comments are locked for this game.
        if ($commentable && $commentable->comments_locked_at) {
            // Only moderators, admins, and the RAdmin account can comment when locked.
            return $user->username === "RAdmin" || $user->hasAnyRole([
                Role::MODERATOR,
                Role::ADMINISTRATOR,
            ]);
        }

        return true;
    }

    public function update(User $user, GameComment $comment): bool
    {
        /*
         * users can edit their own comments
         */
        return $user->is($comment->user);
    }

    public function delete(User $user, GameComment $comment): bool
    {
        // Handle if comments are locked for this game.
        if ($comment->commentable && $comment->commentable->comments_locked_at) {
            // Only moderators and admins can delete when locked.
            return $user->hasAnyRole([
                Role::MODERATOR,
                Role::ADMINISTRATOR,
            ]);
        }

        if ($user->hasAnyRole([
            Role::MODERATOR,
        ])) {
            return true;
        }

        /*
         * it's the user's own comment
         */
        return $user->is($comment->user);
    }

    public function restore(User $user, GameComment $comment): bool
    {
        return false;
    }

    public function forceDelete(User $user, GameComment $comment): bool
    {
        return false;
    }
}
