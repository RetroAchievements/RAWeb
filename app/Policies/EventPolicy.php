<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::EVENT_MANAGER,
            Role::ADMINISTRATOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Event $event): bool
    {
        if ($event->active_from !== null && $event->active_from > Carbon::now()) {
            // future events can only be viewed by users who can manage events
            if (!$user || !$this->manage($user)) {
                return false;
            }
        }

        return true;
    }

    public function viewEventAchievements(?User $user, Event $event): bool
    {
        // the relationship route gates on the parent event, so future events'
        // achievements stay hidden from the public just like the event itself
        return $this->view($user, $event);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::EVENT_MANAGER,
            Role::ADMINISTRATOR,
        ]);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole([
            Role::EVENT_MANAGER,
            Role::ADMINISTRATOR,
        ]);
    }

    public function delete(User $user): bool
    {
        return $user->hasAnyRole([
            Role::EVENT_MANAGER,
            Role::ADMINISTRATOR,
        ]);
    }
}
