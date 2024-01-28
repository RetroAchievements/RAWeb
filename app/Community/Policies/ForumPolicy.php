<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\Forum;
use App\Site\Enums\Permissions;
use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumPolicy
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

    public function view(?User $user, Forum $forum): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::FORUM_MANAGER,
        ]);
    }

    public function update(User $user, Forum $forum): bool
    {
        return $user->hasAnyRole([
            Role::FORUM_MANAGER,
        ]);
    }

    public function delete(User $user, Forum $forum): bool
    {
        return false;
    }

    public function restore(User $user, Forum $forum): bool
    {
        return false;
    }

    public function forceDelete(User $user, Forum $forum): bool
    {
        return false;
    }
}
