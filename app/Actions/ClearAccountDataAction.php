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
        DB::statement("UPDATE users SET
            password = null,
            legacy_salted_password = '',
            connect_token = null,
            web_api_key = null
            WHERE id = :userId", ['userId' => $user->id]
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

        DB::statement("UPDATE users SET
            password = null,
            legacy_salted_password = '',
            email = '',
            email_verified_at = null,
            Permissions = :permissions,
            connect_token = null,
            connect_token_expires_at = null,
            preferences_bitfield = 0,
            last_activity_at = null,
            ManuallyVerified = 0,
            forum_verified_at = null,
            motto = '',
            Untracked = 1,
            unranked_at = :now2,
            web_api_key = null,
            is_user_wall_active = 0,
            rich_presence_game_id = 0,
            rich_presence = null,
            rich_presence_updated_at = null,
            deleted_at = :now
            WHERE id = :userId",
            [
                // Cap permissions to 0 - negative values may stay
                'permissions' => min($user->Permissions, Permissions::Unregistered),
                'userId' => $user->id,
                'now' => Carbon::now(),
                'now2' => Carbon::now(),
            ]
        );

        // TODO use DeleteAvatarAction as soon as media library is in place
        removeAvatar($user->username);

        UserDeleted::dispatch($user);
        UnrankedUser::firstOrCreate(['user_id' => $user->id]);

        // Recalculate top entries for leaderboards that were affected by the deletion.
        $recalculateLeaderboardTopEntryAction = new RecalculateLeaderboardTopEntryAction();
        foreach ($affectedLeaderboardIds as $leaderboardId) {
            $recalculateLeaderboardTopEntryAction->execute($leaderboardId);
        }

        Log::info("Cleared account data: {$user->username} [{$user->id}]");
    }
}
