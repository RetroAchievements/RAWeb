<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketCommentPolicy
{
    use HandlesAuthorization;

    public function create(?User $user, Ticket $commentable): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isMuted() || $user->isBanned()) {
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

    public function delete(User $user, TicketComment $comment): bool
    {
        if ($comment->is_automated) {
            return false;
        }

        if ($user->hasAnyRole([Role::MODERATOR])) {
            return true;
        }

        return $user->is($comment->user);
    }
}
