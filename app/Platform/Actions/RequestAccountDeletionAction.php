<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Models\User;
use App\Notifications\Auth\RequestAccountDeleteNotification;
use Illuminate\Support\Carbon;

class RequestAccountDeletionAction
{
    public function execute(User $user): bool
    {
        // already requested account deletion
        if ($user->delete_requested_at) {
            return false;
        }

        // If the user has elevated permissions, drop them to Registered
        $currentPermissions = $user->getAttribute('Permissions');
        if ($currentPermissions > Permissions::Registered) {
            $user->setAttribute('Permissions', Permissions::Registered);

            $user->roles()->detach();
            $user->permissions()->detach();

            updateClaimsForPermissionChange($user, Permissions::Registered, $currentPermissions);
        }

        $user->delete_requested_at = Carbon::now();
        $user->saveQuietly();

        addArticleComment('Server', CommentableType::UserModeration, $user->id,
            $user->display_name . ' requested account deletion'
        );

        $user->notify(new RequestAccountDeleteNotification());

        return true;
    }
}
