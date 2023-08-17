<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\News;
use App\Site\Enums\Permissions;
use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->Permissions >= Permissions::Developer;
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
        // TODO: Developers used to be able to write new posts -> ?

        return $user->hasAnyRole([
            // Role::ADMINISTRATOR,
            Role::NEWS_MANAGER,
        ]);
    }

    public function update(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            // Role::ADMINISTRATOR,
            Role::MODERATOR,
            Role::NEWS_MANAGER,
        ]);
    }

    public function delete(User $user, News $news): bool
    {
        return false;
    }

    public function restore(User $user, News $news): bool
    {
        return false;
    }

    public function forceDelete(User $user, News $news): bool
    {
        return false;
    }

    public function deleteImage(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            // Role::ADMINISTRATOR,
            Role::MODERATOR,
            Role::NEWS_MANAGER,
        ]);
    }
}
