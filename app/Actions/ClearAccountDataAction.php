<?php

declare(strict_types=1);

namespace App\Actions;

use App\Community\Actions\DeleteMessageThreadAction;
use App\Community\Enums\ArticleType;
use App\Community\Enums\TicketState;
use App\Enums\Permissions;
use App\Events\UserDeleted;
use App\Models\Comment;
use App\Models\Leaderboard;
use App\Models\Ticket;
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
            appToken = null,
            APIKey = null
            WHERE ID = :userId", ['userId' => $user->ID]
        );

        $this->closeUnresolvedTickets($user);

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
            appToken = null,
            appTokenExpiry = null,
            websitePrefs = 0,
            LastLogin = null,
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

    private function closeUnresolvedTickets(User $user): void
    {
        $unresolvedTickets = Ticket::query()
            ->unresolved()
            ->where('reporter_id', $user->id)
            ->with('author')
            ->get();

        if ($unresolvedTickets->isEmpty()) {
            return;
        }

        $now = Carbon::now();
        foreach ($unresolvedTickets as $ticket) {
            $ticket->update([
                'ReportState' => TicketState::Closed,
                'ResolvedAt' => $now,
                'resolver_id' => null,
            ]);

            Comment::create([
                'ArticleType' => ArticleType::AchievementTicket,
                'ArticleID' => $ticket->id,
                'Payload' => 'Ticket closed: Reporter account deleted.',
                'user_id' => Comment::SERVER_USER_ID,
            ]);

            if ($ticket->author) {
                expireUserTicketCounts($ticket->author);
            }
        }
    }
}
