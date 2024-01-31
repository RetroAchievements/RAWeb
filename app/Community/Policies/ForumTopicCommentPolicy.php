<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\ForumTopic;
use App\Community\Models\ForumTopicComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumTopicCommentPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::FORUM_MANAGER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, ForumTopicComment $comment): bool
    {
        // no route
        return true;
    }

    public function create(User $user, ?ForumTopic $commentable): bool
    {
        /*
         * verified and unverified users may comment
         * muted, suspended, banned may not comment
         */
        if ($user->isMuted()) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        return true;
    }

    public function update(User $user, ForumTopicComment $comment): bool
    {
        /*
         * users can edit their own comments
         */
        return $user->is($comment->user);
    }

    public function delete(User $user, ForumTopicComment $comment): bool
    {
        if ($comment->deleted_at) {
            return false;
        }

        if ($user->hasAnyRole([
            Role::MODERATOR,
        ])) {
            return true;
        }

        /*
         * users can delete their own comments
         */
        return $user->is($comment->user);
    }

    public function restore(User $user, ForumTopicComment $comment): bool
    {
        return false;
    }

    public function forceDelete(User $user, ForumTopicComment $comment): bool
    {
        return false;
    }
}
