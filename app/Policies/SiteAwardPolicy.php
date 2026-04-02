<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\AwardType;
use App\Models\Role;
use App\Models\SiteAward;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SiteAwardPolicy
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

    public function view(?User $user, SiteAward $siteAward): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, SiteAward $siteAward): bool
    {
        return $this->canManageAward($user, $siteAward);
    }

    public function delete(User $user, SiteAward $siteAward): bool
    {
        return $this->canManageAward($user, $siteAward);
    }

    private function canManageAward(User $user, SiteAward $siteAward): bool
    {
        if ($user->hasRole(Role::ADMINISTRATOR)) {
            return true;
        }

        // Playtest Managers can only manage playtest awards.
        if ($user->hasRole(Role::PLAYTEST_MANAGER)) {
            return $siteAward->award_type === AwardType::Playtest;
        }

        return false;
    }
}
