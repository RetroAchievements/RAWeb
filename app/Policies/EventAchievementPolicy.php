<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\EventAchievement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventAchievementPolicy
{
    use HandlesAuthorization;

    public function __construct(private EventPolicy $eventPolicy)
    {
    }

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

    public function view(?User $user, EventAchievement $eventAchievement): bool
    {
        if (!$eventAchievement->achievement->is_promoted && !$user?->can('manage', $eventAchievement)) {
            return false;
        }

        $events = Event::query()
            ->where('legacy_game_id', $eventAchievement->achievement->game_id)
            ->get();

        return
            $events->isNotEmpty()
            && $events->every(fn (Event $event): bool => $this->eventPolicy->view($user, $event));
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
