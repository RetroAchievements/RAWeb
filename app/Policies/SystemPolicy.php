<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
            Role::GAME_EDITOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, System $system): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function update(User $user, System $system): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
            Role::GAME_EDITOR,
        ]);
    }

    public function delete(User $user, System $system): bool
    {
        if ($system->active) {
            return false;
        }

        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function restore(User $user, System $system): bool
    {
        return $user->hasAnyRole([
            Role::RELEASE_MANAGER,
        ]);
    }

    public function forceDelete(User $user, System $system): bool
    {
        return false;
    }

    public function updateField(User $user, System $system, string $fieldName): bool
    {
        $roleFieldPermissions = [
            Role::GAME_EDITOR => [
                'has_analog_tv_output',
                'screenshot_resolutions',
                'supports_upscaled_screenshots',
            ],
        ];

        // These roles can edit everything.
        if ($user->hasAnyRole([Role::ROOT, Role::RELEASE_MANAGER])) {
            return true;
        }

        $userRoles = $user->getRoleNames();

        $allowedFieldsForUser = collect($roleFieldPermissions)
            ->filter(fn ($fields, $role) => $userRoles->contains($role))
            ->collapse()
            ->unique()
            ->all();

        return in_array($fieldName, $allowedFieldsForUser, true);
    }
}
