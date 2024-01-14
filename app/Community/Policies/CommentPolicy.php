<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\Comment;
use App\Site\Models\Role;
use App\Site\Models\User;
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
}
