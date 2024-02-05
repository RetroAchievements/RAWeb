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

    public function create(User $user, ?Game $commentable): bool
    {
        if ($user->isMuted()) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
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
        if ($user->hasAnyRole([
            Role::MODERATOR,
        ])) {
            return true;
        }

        /*
         * it's the user's own comment
         */
        return $user->is($comment->commentable);
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
