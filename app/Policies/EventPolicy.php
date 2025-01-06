<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
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

    public function view(?User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasRole(Role::EVENT_MANAGER);
    }
}
