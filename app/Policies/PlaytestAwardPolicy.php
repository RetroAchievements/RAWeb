<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PlaytestAward;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlaytestAwardPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::PLAYTEST_MANAGER,
            Role::ADMINISTRATOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, PlaytestAward $playtestAward): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, PlaytestAward $playtestAward): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, PlaytestAward $playtestAward): bool
    {
        return $this->manage($user);
    }
}
