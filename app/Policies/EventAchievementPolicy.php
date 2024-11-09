<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventAchievement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventAchievementPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, EventAchievement $eventAchievement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }

    public function update(User $user, EventAchievement $eventAchievement): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }

    public function delete(User $user, EventAchievement $eventAchievement): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }
}
