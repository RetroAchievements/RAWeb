<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\TriggerTicket;
use App\Site\Enums\Permissions;
use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TriggerTicketPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::HUB_MANAGER,
            Role::DEVELOPER_STAFF,
            Role::DEVELOPER,
            Role::DEVELOPER_JUNIOR,
        ])
            || $user->getAttribute('Permissions') >= Permissions::JuniorDeveloper;
    }

    public function view(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    public function delete(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    public function restore(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }

    public function forceDelete(User $user, TriggerTicket $achievementTicket): bool
    {
        return false;
    }
}
