<?php

declare(strict_types=1);

namespace App\Policies;

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
            Role::ADMINISTRATOR,
            Role::MODERATOR,
            Role::FORUM_MANAGER,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ForumTopic $topic): bool
    {
        if (!$user && $topic->required_permissions > 0) {
            return false;
        }

        if ($user && $topic->required_permissions > 0) {
            $userPermissions = (int) $user->getAttribute('Permissions');
            if ($userPermissions < $topic->required_permissions) {
                return false;
            }
        }

        return true;
    }

    public function create(User $user, Forum $forum): bool
    {
        if ($user->isMuted() || $user->isBanned()) {
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

    public function gate(User $user, ForumTopic $topic): bool
    {
        return $this->manage($user);
    }

    public function lock(User $user, ForumTopic $topic): bool
    {
        return $this->manage($user);
    }
}
