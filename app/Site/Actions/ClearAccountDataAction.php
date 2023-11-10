<?php

declare(strict_types=1);

namespace App\Site\Actions;

use App\Site\Enums\Permissions;
use App\Site\Events\UserDeleted;
use App\Site\Models\User;
use DB;
use Illuminate\Support\Facades\Log;

class ClearAccountDataAction
{
    public function execute(User $user): void
    {
        // disable account access while we destroy it (prevents creating new records during delete)
        DB::statement("UPDATE UserAccounts u SET
            u.Password = null,
            u.SaltedPass = '',
            u.cookie = null,
            u.appToken = null,
            u.APIKey = null
            WHERE u.ID = :userId", ['userId' => $user->ID]
        );

        // TODO $user->activities()->delete();
        // TODO $user->emailConfirmations()->delete();
        DB::statement('DELETE FROM EmailConfirmations WHERE User = :username', ['username' => $user->User]);
        // TODO $user->followers()->delete();
        // TODO $user->following()->delete();
        DB::statement('DELETE FROM Friends WHERE User = :username OR Friend = :friendUsername', ['username' => $user->User, 'friendUsername' => $user->User]);
        // TODO $user->ratings()->delete();
        DB::statement('DELETE FROM Rating WHERE User = :username', ['username' => $user->User]);
        // TODO $user->achievementSetRequests()->delete();
        DB::statement('DELETE FROM SetRequest WHERE User = :username', ['username' => $user->User]);
        // TODO $user->badges()->delete();
        DB::statement('DELETE FROM SiteAwards WHERE User = :username', ['username' => $user->User]);
        // TODO $user->subscriptions()->delete();
        DB::statement('DELETE FROM Subscription WHERE UserID = :userId', ['userId' => $user->ID]);

        DB::statement("UPDATE UserAccounts u SET
            u.Password = null,
            u.SaltedPass = '',
            u.EmailAddress = '',
            u.Permissions = :permissions,
            u.fbUser = 0,
            u.fbPrefs = null,
            u.cookie = null,
            u.appToken = null,
            u.appTokenExpiry = null,
            u.websitePrefs = 0,
            u.LastLogin = null,
            u.LastActivityID = 0,
            u.Motto = '',
            u.Untracked = 1,
            u.APIKey = null,
            u.UserWallActive = 0,
            u.LastGameID = 0,
            u.RichPresenceMsg = null,
            u.RichPresenceMsgDate = null,
            u.PasswordResetToken = null,
            u.Deleted = NOW()
            WHERE ID = :userId",
            [
                // Cap permissions to 0 - negative values may stay
                'permissions' => min($user->Permissions, Permissions::Unregistered),
                'userId' => $user->ID,
            ]
        );

        // TODO use DeleteAvatarAction as soon as media library is in place
        removeAvatar($user->User);

        UserDeleted::dispatch($user);

        Log::info("Cleared account data: {$user->User} [{$user->ID}]");
    }
}
