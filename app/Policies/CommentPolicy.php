<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\ArticleType;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

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
        return true;
    }

    public function delete(User $user, Comment $comment): bool
    {
        // users can delete their own comments
        if ($comment->user_id == $user->id) {
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
