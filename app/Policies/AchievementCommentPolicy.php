<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Achievement;
use App\Models\AchievementComment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementCommentPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function viewAny(?User $user, Achievement $commentable): bool
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

    public function view(User $user, AchievementComment $comment): bool
    {
        return true;
    }

    public function create(User $user, ?Achievement $commentable): bool
    {
        if ($user->isMuted()) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        return true;
    }

    public function update(User $user, AchievementComment $comment): bool
    {
        /*
         * users can edit their own comments
         */
        return $user->is($comment->user);
    }

    public function delete(User $user, AchievementComment $comment): bool
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

    public function restore(User $user, AchievementComment $comment): bool
    {
        return false;
    }

    public function forceDelete(User $user, AchievementComment $comment): bool
    {
        return false;
    }
}
