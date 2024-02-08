<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permissions;
use App\Models\News;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->getAttribute('Permissions') >= Permissions::Developer;
        // return $user->hasAnyRole([
        //     // Role::ADMINISTRATOR,
        //     Role::MODERATOR,
        //     Role::NEWS_MANAGER,
        // ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, News $news): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::NEWS_MANAGER,
        ]);
    }

    public function update(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            Role::NEWS_MANAGER,
        ]);
    }

    public function delete(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            Role::NEWS_MANAGER,
        ]);
    }

    public function restore(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            Role::NEWS_MANAGER,
        ]);
    }

    public function forceDelete(User $user, News $news): bool
    {
        return false;
    }

    public function deleteImage(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            Role::MODERATOR,
            Role::NEWS_MANAGER,
        ]);
    }
}
