<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\User;
use Illuminate\Support\Carbon;

class RequestAccountDeletion
{
    public function execute(User $user): bool
    {
        // already requested account deletion
        if ($user->DeleteRequested) {
            return false;
        }

        // If the user has elevated permissions, drop them to Registered
        $currentPermissions = $user->getAttribute('Permissions');
        if ($currentPermissions > Permissions::Registered) {
            $user->setAttribute('Permissions', Permissions::Registered);

            updateClaimsForPermissionChange($user, Permissions::Registered, $currentPermissions);
        }

        $user->DeleteRequested = Carbon::now();
        $user->save();

        addArticleComment('Server', ArticleType::UserModeration, $user->ID,
            $user->User . ' requested account deletion'
        );

        mail_utf8($user->EmailAddress, "Account Deletion Request",
            "Hello {$user->User},<br><br>" .
            "Your account has been marked for deletion.<br>" .
            "If you do not cancel this request before " . getDeleteDate($user->DeleteRequested) . ", " .
            "you will no longer be able to access your account.<br><br>" .
            "Thanks!<br>" .
            "-- Your friends at RetroAchievements.org<br>");

        return true;
    }
}
