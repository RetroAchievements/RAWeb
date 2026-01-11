<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\EventComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventCommentPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function viewAny(?User $user, Event $commentable): bool
    {
        return true;
    }

    public function view(User $user, EventComment $comment): bool
    {
        return true;
    }

    public function create(?User $user, ?Event $commentable): bool
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

        return true;
    }

    public function update(User $user, EventComment $comment): bool
    {
        return $user->is($comment->user);
    }

    public function delete(User $user, EventComment $comment): bool
    {
        if ($comment->is_automated) {
            return false;
        }

        if ($user->hasAnyRole([
            Role::MODERATOR,
        ])) {
            return true;
        }

        // Users can delete their own comments.
        return $user->is($comment->user);
    }

    public function restore(User $user, EventComment $comment): bool
    {
        return false;
    }

    public function forceDelete(User $user, EventComment $comment): bool
    {
        return false;
    }
}
