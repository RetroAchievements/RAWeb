<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\Comment;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function view(?User $user, Comment $comment): bool
    {
        return true;
    }
}
