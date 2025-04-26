<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumTopicCommentPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MODERATOR,
            Role::FORUM_MANAGER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ForumTopicComment $comment): bool
    {
        if ($user) {
            // Users are allowed to see their own unauthorized comments.
            // If the user is allowed to manage ForumTopicComment entities,
            // they can also view the comment.
            if ($this->manage($user) || $user->is($comment->user)) {
                return true;
            }
        }

        // If the comment is authorized, then it's publicly viewable.
        if ($comment->is_authorized) {
            return true;
        }

        return false;
    }

    public function create(User $user, ForumTopic $topic): bool
    {
        /*
         * verified and unverified users may comment
         * muted, suspended, banned may not comment
         */
        if ($user->isMuted()) {
            return false;
        }

        /*
         * users may not reply to locked topics, unless they have
         * the ability to lock/unlock topics themselves.
         */
        if ($topic->is_locked && !$user->can('lock', $topic)) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        return true;
    }

    public function update(User $user, ForumTopicComment $comment): bool
    {
        // Muted users might edit their existing comments to post abuse.
        // Therefore, we will not allow muted users to edit their comments.
        if ($user->isMuted() || $user->isBanned()) {
            return false;
        }

        // Locked topics cannot have any of their posts edited, unless it's
        // being edited by someone with authority.
        if ($comment->forumTopic->is_locked && !$this->manage($user)) {
            return false;
        }

        // Otherwise, users are allowed to edit their own comments.
        // If the user is allowed to manage ForumTopicComment entities,
        // they can also edit the comment.
        return $this->manage($user) || $user->is($comment->user);
    }

    public function delete(User $user, ForumTopicComment $comment): bool
    {
        if ($comment->deleted_at) {
            return false;
        }

        // Users are allowed to delete their own comments.
        // If the user is allowed to manage ForumTopicComment entities,
        // they can also delete the comment.
        return $this->manage($user) || $user->is($comment->user);
    }

    public function restore(User $user, ForumTopicComment $comment): bool
    {
        return false;
    }

    public function forceDelete(User $user, ForumTopicComment $comment): bool
    {
        return false;
    }

    public function viewUserPosts(User $currentUser, User $targetUser): bool
    {
        return !$targetUser->isBlocking($currentUser);
    }
}
