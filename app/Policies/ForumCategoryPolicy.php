<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ForumCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ForumCategoryPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::FORUM_MANAGER,
        ]);
    }

    public function view(User $user, ForumCategory $forumCategory): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::FORUM_MANAGER,
        ]);
    }

    public function update(User $user, ForumCategory $forumCategory): bool
    {
        return $user->hasAnyRole([
            Role::FORUM_MANAGER,
        ]);
    }

    public function delete(User $user, ForumCategory $forumCategory): bool
    {
        return false;
    }

    public function restore(User $user, ForumCategory $forumCategory): bool
    {
        return false;
    }

    public function forceDelete(User $user, ForumCategory $forumCategory): bool
    {
        return false;
    }
}
