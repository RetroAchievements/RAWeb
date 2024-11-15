<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\ArticleType;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use App\Models\UserComment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class CommentPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
        ]);
    }

    public function view(?User $user, Comment $comment): bool
    {
        return $user->isNotBanned();
    }

    public function viewAny(?User $user, Model $commentable): bool
    {
        /*
         * check guests first
         */
        if (!$user) {
            return true;
        }

        if ($user->isBanned()) {
            return false;
        }

        return true;
    }

    public function create(?User $user, ?Model $commentable = null, ?int $articleType = null): bool
    {
        if ($user?->isMuted()) {
            // Even when muted, developers may still comment on tickets for their own achievements.
            // TODO this is silly. delete all of this.
            if ($commentable !== null && $commentable instanceof \App\Models\Ticket) {
                $commentable->loadMissing(['achievement.developer']);

                $didAuthorAchievement = $commentable->achievement->developer->id === $user->id;

                return
                    $didAuthorAchievement
                    && $commentable->is_open
                    && $user->hasAnyRole([Role::DEVELOPER_STAFF, Role::DEVELOPER]);
            }

            return false;
        }

        if ($user && !$user->hasVerifiedEmail()) {
            return false;
        }

        if (
            $commentable !== null
            && $commentable instanceof User
            && $articleType !== ArticleType::UserModeration
        ) {
            return $user?->can('create', [UserComment::class, $commentable]);
        }

        return true;
    }

    public function delete(User $user, Comment $comment): bool
    {
        // server-written comments cannot be deleted
        if ($comment->is_automated) {
            return false;
        }

        // users can delete their own comments
        if ($user->is($comment->user)) {
            return true;
        }

        // users can delete any comment off of their wall
        if ($comment->ArticleType == ArticleType::User && $comment->ArticleID == $user->id) {
            return true;
        }

        // moderators can delete any comment
        return $this->manage($user);
    }
}
