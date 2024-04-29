<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\Forum;
use App\Models\ForumTopic;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumTopicPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
            Role::FORUM_MANAGER,
        ])
            || $user->getAttribute('Permissions') >= Permissions::Moderator;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ForumTopic $topic): bool
    {
        /*
         * TODO: check forum restrictions
         */
        return true;
    }

    public function create(User $user, Forum $forum): bool
    {
        if ($user->isMuted()) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        return true;
    }

    public function update(User $user, ForumTopic $topic): bool
    {
        // Muted users might update the topic title (or other properties)
        // to abuse other users or the platform. We won't allow muted users
        // to update topics.
        if ($user->isMuted()) {
            return false;
        }

        return $this->manage($user) || $user->is($topic->user);
    }

    public function delete(User $user, ForumTopic $topic): bool
    {
        return $this->manage($user);
    }

    public function restore(User $user, ForumTopic $topic): bool
    {
        return false;
    }

    public function forceDelete(User $user, ForumTopic $topic): bool
    {
        return false;
    }
}
