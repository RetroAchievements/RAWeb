<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\News;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::EVENT_MANAGER,
            Role::MODERATOR,
            Role::NEWS_MANAGER,
            Role::TEAM_ACCOUNT,
        ]);
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
            Role::ADMINISTRATOR,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::EVENT_MANAGER,
            Role::MODERATOR,
            Role::NEWS_MANAGER,
            Role::TEAM_ACCOUNT,
        ]);
    }

    public function update(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::EVENT_MANAGER,
            Role::MODERATOR,
            Role::NEWS_MANAGER,
            Role::TEAM_ACCOUNT,
        ]);
    }

    public function delete(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MODERATOR,
            Role::NEWS_MANAGER,
        ]);
    }

    public function restore(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::MODERATOR,
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

    public function publish(User $user, News $news): bool
    {
        // TODO
        return false;
    }

    public function unpublish(User $user, News $news): bool
    {
        // TODO
        return false;
    }

    public function pin(User $user, News $news): bool
    {
        return $user->hasAnyRole([
            Role::ADMINISTRATOR,
            Role::NEWS_MANAGER,
        ]);
    }
}
