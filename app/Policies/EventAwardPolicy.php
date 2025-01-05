<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventAward;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventAwardPolicy
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

    public function view(?User $user, EventAward $eventAward): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }

    public function update(User $user, EventAward $eventAward): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }

    public function delete(User $user, EventAward $eventAward): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }
}
