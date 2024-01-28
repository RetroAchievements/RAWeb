<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\Forum;
use App\Community\Models\ForumTopic;
use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumTopicPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::FORUM_MANAGER,
        ]);
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
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, ForumTopic $topic): bool
    {
        return $user->is($topic->user);
    }

    public function delete(User $user, ForumTopic $topic): bool
    {
        if ($user->hasAnyRole([
            Role::MODERATOR,
        ])) {
            return true;
        }

        return $user->is($topic->user);
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
