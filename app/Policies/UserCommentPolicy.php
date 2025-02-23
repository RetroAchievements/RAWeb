<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\UserComment;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserCommentPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
            Role::FORUM_MANAGER,
        ]);
    }

    public function viewAny(?User $user, User $commentable): bool
    {
        if (!$commentable->UserWallActive || $commentable->banned_at) {
            return false;
        }

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

    public function view(User $user, UserComment $comment): bool
    {
        return true;
    }

    public function create(User $user, ?User $commentable): bool
    {
        /**
         * TODO check user privacy settings
         */
        if (
            !$commentable
            || $user->is_muted
            || !$user->hasVerifiedEmail()
            || $user->isFreshAccount()
            || $commentable->isBlocking($user)
            || !$commentable->UserWallActive
            || (
                !$user->is($commentable)
                && $commentable->only_allows_contact_from_followers
                && !$commentable->isFollowing($user)
            )
        ) {
            return false;
        }

        return true;
    }

    public function update(User $user, UserComment $comment): bool
    {
        /*
         * users can edit their own comments
         */
        return $user->is($comment->user);
    }

    public function delete(User $user, UserComment $comment): bool
    {
        if ($comment->is_automated) {
            return false;
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

    public function restore(User $user, UserComment $comment): bool
    {
        return false;
    }

    public function forceDelete(User $user, UserComment $comment): bool
    {
        return false;
    }
}
