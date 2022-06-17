<?php

declare(strict_types=1);

namespace App\Site\Actions;

use App\Site\Models\User;
use Illuminate\Http\Request;

class UpdateRolesAction
{
    public function execute(User $user, Request $request): void
    {
        /**
         * roles currently attached to the user
         */
        $currentRoles = $user->roles->pluck('name')->toArray();

        /**
         * make sure only roles are attached/removed that the request users is allowed to
         */
        $assignableRoles = $request->user()->assignableRoles->toArray();

        /**
         * might be empty if all are removed
         */
        $submittedRoles = $request->get('roles', []);
        // dump($submittedRoles);

        $removeRoles = [];
        foreach ($currentRoles as $currentRole) {
            if (!in_array($currentRole, $assignableRoles)) {
                continue;
            }
            if (!in_array($currentRole, $submittedRoles)) {
                $removeRoles[] = $currentRole;
                $user->removeRole($currentRole);
            }
        }
        // dump($removeRoles);

        $addRoles = [];
        foreach ($submittedRoles as $submittedRole) {
            if (!in_array($submittedRole, $assignableRoles)) {
                continue;
            }
            if (!in_array($submittedRole, $currentRoles)) {
                $addRoles[] = $submittedRole;
                $user->assignRole($submittedRole);
            }
        }
        // dump($addRoles);
        // dd($currentRoles);
    }
}
