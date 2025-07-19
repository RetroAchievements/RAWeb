<?php

declare(strict_types=1);

namespace App\Actions;

use App\Community\Actions\DeleteMessageThreadAction;
use App\Enums\Permissions;
use App\Events\UserDeleted;
use App\Models\Leaderboard;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Platform\Actions\RecalculateLeaderboardTopEntryAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $user->emailConfirmations()->delete();
        $user->relatedUsers()->detach();
        $user->inverseRelatedUsers()->detach();
        $user->gameListEntries()->delete();
        $user->playerBadges()->delete();
        $user->playerStats()->delete();
        $user->playerSessions()->delete();

        // Find leaderboards where this user currently has the top entry.
        // We'll need to reset those denormalized top entries.
        $affectedLeaderboardIds = Leaderboard::whereHas('topEntry', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->pluck('ID');

        $user->leaderboardEntries()->delete();
        $user->subscriptions()->delete();

        // use action to delete each participation so threads with no remaining active participants get cleaned up
        $deleteMessageThreadAction = new DeleteMessageThreadAction();
        foreach ($user->messageThreadParticipations()->with('thread')->get() as $participation) {
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
            unranked_at = :now2,
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
                'now2' => Carbon::now(),
            ]
        );

        // TODO use DeleteAvatarAction as soon as media library is in place
        removeAvatar($user->User);

        UserDeleted::dispatch($user);
        UnrankedUser::firstOrCreate(['user_id' => $user->ID]);

        // Recalculate top entries for leaderboards that were affected by the deletion.
        $recalculateLeaderboardTopEntryAction = new RecalculateLeaderboardTopEntryAction();
        foreach ($affectedLeaderboardIds as $leaderboardId) {
            $recalculateLeaderboardTopEntryAction->execute($leaderboardId);
        }

        Log::info("Cleared account data: {$user->User} [{$user->ID}]");
    }
}
