<?php

declare(strict_types=1);

namespace App\Actions;

use App\Community\Actions\DeleteMessageThreadAction;
use App\Enums\Permissions;
use App\Events\UserDeleted;
use App\Models\User;
use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ClearAccountDataAction
{
    public function execute(User $user): void
    {
        // disable account access while we destroy it (prevents creating new records during delete)
        DB::statement("UPDATE UserAccounts SET
            Password = null,
            SaltedPass = '',
            cookie = null,
            appToken = null,
            APIKey = null
            WHERE ID = :userId", ['userId' => $user->ID]
        );

        // TODO $user->activities()->delete();
        // TODO $user->emailConfirmations()->delete();
        DB::statement('DELETE FROM EmailConfirmations WHERE User = :username', ['username' => $user->User]);
        // TODO $user->followers()->delete();
        // TODO $user->following()->delete();
        DB::statement('DELETE FROM Friends WHERE User = :username OR Friend = :friendUsername', ['username' => $user->User, 'friendUsername' => $user->User]);
        // TODO $user->achievementSetRequests()->delete();
        DB::statement('DELETE FROM SetRequest WHERE User = :username', ['username' => $user->User]);
        // TODO $user->badges()->delete();
        DB::statement('DELETE FROM SiteAwards WHERE User = :username', ['username' => $user->User]);
        $user->subscriptions()->delete();

        // use action to delete each participation so threads with no remaing active participants get cleaned up
        $deleteMessageThreadAction = new DeleteMessageThreadAction();
        foreach ($user->messageThreadParticipations()->get() as $participation) {
            $deleteMessageThreadAction->execute($participation->thread, $user);
        }

        DB::statement("UPDATE UserAccounts SET
            Password = null,
            SaltedPass = '',
            EmailAddress = '',
            email_verified_at = null,
            Permissions = :permissions,
            fbUser = 0,
            fbPrefs = null,
            cookie = null,
            appToken = null,
            appTokenExpiry = null,
            websitePrefs = 0,
            LastLogin = null,
            LastActivityID = 0,
            ManuallyVerified = 0,
            forum_verified_at = null,
            Motto = '',
            Untracked = 1,
            APIKey = null,
            UserWallActive = 0,
            LastGameID = 0,
            RichPresenceMsg = null,
            RichPresenceMsgDate = null,
            PasswordResetToken = null,
            Deleted = :now
            WHERE ID = :userId",
            [
                // Cap permissions to 0 - negative values may stay
                'permissions' => min($user->Permissions, Permissions::Unregistered),
                'userId' => $user->ID,
                'now' => Carbon::now(),
            ]
        );

        // TODO use DeleteAvatarAction as soon as media library is in place
        removeAvatar($user->User);

        UserDeleted::dispatch($user);

        Log::info("Cleared account data: {$user->User} [{$user->ID}]");
    }
}
