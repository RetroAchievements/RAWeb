<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\News;
use App\Models\NewsComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsCommentPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return false;
    }

    public function viewAny(?User $user, News $commentable): bool
    {
        /*
         * check guests first
         */
        if (!$user) {
            return true;
        }

        if ($user->hasAnyRole([
            Role::MODERATOR,
            // Role::ADMINISTRATOR,
        ])) {
            return true;
        }

        return true;
    }

    public function view(User $user, NewsComment $comment): bool
    {
        return true;
    }

    public function create(User $user, ?News $commentable): bool
    {
        if ($user->is_muted) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        return true;
    }

    public function update(User $user, NewsComment $comment): bool
    {
        /*
         * users can edit their own comments
         */
        return $user->is($comment->user);
    }

    public function delete(User $user, NewsComment $comment): bool
    {
        if ($user->hasAnyRole([
            Role::MODERATOR,
            // Role::ADMINISTRATOR,
        ])) {
            return true;
        }

        /*
         * it's the user's own comment
         */
        return $user->is($comment->commentable);
    }

    public function restore(User $user, NewsComment $comment): bool
    {
        return false;
    }

    public function forceDelete(User $user, NewsComment $comment): bool
    {
        return false;
    }
}
