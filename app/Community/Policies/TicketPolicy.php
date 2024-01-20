<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\Ticket;
use App\Site\Enums\Permissions;
use App\Site\Models\Role;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
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

    public function view(User $user, Ticket $ticket): bool
    {
        return true;
    }
}
