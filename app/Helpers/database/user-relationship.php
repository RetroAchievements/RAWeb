<?php

use App\Community\Enums\UserRelationship;
use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Models\Game;
use App\Models\PlayerSession;
use App\Models\User;
use App\Models\UserRelation;

function changeFriendStatus(User $senderUser, User $targetUser, int $newStatus): string
{
    $existingUserRelation = UserRelation::where('user_id', $senderUser->id)
        ->where('related_user_id', $targetUser->id)
        ->first();

    $newRelationship = false;
    if ($existingUserRelation) {
        $oldStatus = $existingUserRelation->Friendship;
    } else {
        $newRelationship = true;
        $oldStatus = UserRelationship::NotFollowing;
    }

    if (
        $newStatus === UserRelationship::Following
        && ($targetUser->isBlocking($senderUser) || $senderUser->isFreshAccount())
    ) {
        // don't allow follows if one user is blocking the other, or if the
        // person initiating the follow has little to no activity on their account
        return "error";
    }

    // Upsert the relationship.
    if ($existingUserRelation) {
        $existingUserRelation->Friendship = $newStatus;
        $existingUserRelation->save();
    } else {
        UserRelation::create([
            'user_id' => $senderUser->id,
            'related_user_id' => $targetUser->id,
            'Friendship' => $newStatus,
        ]);
    }

    switch ($newStatus) {
        case UserRelationship::Following:
            // attempt to notify the target of the new follower
            if ($newRelationship && BitSet($targetUser->websitePrefs, UserPreference::EmailOn_Followed)) {
                // notify the new friend of the request
                sendFriendEmail($targetUser->display_name, $targetUser->EmailAddress, 0, $senderUser->display_name);
            }

            return "user_follow";

        case UserRelationship::NotFollowing:
            return match ($oldStatus) {
                UserRelationship::Following => "user_unfollow",
                UserRelationship::Blocked => "user_unblock",
                default => "error",
            };

        case UserRelationship::Blocked:
            if (!$targetUser->isBlocking($senderUser)) {
                // if the other user hasn't blocked the user, clear out their friendship status too
                UserRelation::where('user_id', $targetUser->id)
                    ->where('related_user_id', $senderUser->id)
                    ->update(['Friendship' => UserRelationship::NotFollowing]);
            }

            return "user_block";

        default:
            return "error";
    }
}

function GetFriendList(User $user): array
{
    // ASSERT: this is only called from the Connect getfriendlist API.
    // only return the 100 most recently active friends (some users have more than 1000!)
    $friends = $user->followedUsers()
        ->where('UserAccounts.Permissions', '>=', Permissions::Unregistered)
        ->whereNull('UserAccounts.Deleted')
        ->orderBy('UserAccounts.LastLogin', 'DESC')
        ->limit(100)
        ->select([
            'UserAccounts.ID',
            'UserAccounts.User',
            'UserAccounts.display_name',
            'UserAccounts.RAPoints',
            'UserAccounts.RichPresenceMsg',
            'UserAccounts.RichPresenceMsgDate',
            'UserAccounts.LastLogin',
            'UserAccounts.LastGameID',
        ])
        ->get();

    if ($friends->isEmpty()) {
        return [];
    }

    $friendIds = $friends->pluck('ID')->toArray();

    // Get the most recent session date for each friend.
    $subQuery = PlayerSession::selectRaw('user_id, MAX(rich_presence_updated_at) as max_date')
        ->whereIn('user_id', $friendIds)
        ->whereNotNull('rich_presence_updated_at')
        ->groupBy('user_id');

    // Get full session details by joining with the subquery.
    $latestSessions = PlayerSession::from('player_sessions as ps')
        ->joinSub($subQuery, 'latest', function ($join) {
            $join
                ->on('ps.user_id', '=', 'latest.user_id')
                ->on('ps.rich_presence_updated_at', '=', 'latest.max_date');
        })
        ->with(['game:ID,Title,ImageIcon'])
        ->select([
            'ps.user_id',
            'ps.rich_presence',
            'ps.rich_presence_updated_at',
            'ps.game_id',
        ])
        ->get()
        ->keyBy('user_id');

    // Get game IDs for users without recent sessions.
    $gameIds = $friends
        ->filter(function ($friend) use ($latestSessions) {
            return !isset($latestSessions[$friend->ID]) && $friend->LastGameID > 0;
        })
        ->pluck('LastGameID')
        ->unique()
        ->toArray();

    // Get game data for missing games.
    $games = empty($gameIds)
        ? collect()
        : Game::query()
            ->whereIn('ID', $gameIds)
            ->select(['ID', 'Title', 'ImageIcon'])
            ->get()
            ->keyBy('ID');

    $friendList = [];

    foreach ($friends as $friend) {
        $entry = [
            'Friend' => $friend->display_name,
            'AvatarUrl' => media_asset('UserPic/' . $friend->User . '.png'),
            'RAPoints' => $friend->RAPoints,
            'LastSeen' => empty($friend->RichPresenceMsg) ? 'Unknown' : strip_tags($friend->RichPresenceMsg),
            'LastSeenTime' => ($friend->RichPresenceMsgDate ?? $friend->LastLogin)?->unix(),
        ];

        if (isset($latestSessions[$friend->id])) {
            $mostRecentSession = $latestSessions[$friend->id];
            $entry['LastSeen'] = $mostRecentSession->rich_presence;
            $entry['LastSeenTime'] = strtotime($mostRecentSession->rich_presence_updated_at);

            if ($mostRecentSession->game) {
                $entry['LastGameId'] = $mostRecentSession->game_id;
                $entry['LastGameTitle'] = $mostRecentSession->game->title;
                $entry['LastGameIconUrl'] = media_asset($mostRecentSession->game->ImageIcon);
            } else {
                $entry['LastGameId'] = $mostRecentSession->game_id;
                $entry['LastGameTitle'] = null;
                $entry['LastGameIconUrl'] = null;
            }
        } elseif ($friend->LastGameID && isset($games[$friend->LastGameID])) {
            $lastGame = $games[$friend->LastGameID];
            $entry['LastGameId'] = $lastGame->id;
            $entry['LastGameTitle'] = $lastGame->title;
            $entry['LastGameIconUrl'] = media_asset($lastGame->ImageIcon);
        } else {
            $entry['LastGameId'] = null;
            $entry['LastGameTitle'] = null;
            $entry['LastGameIconUrl'] = null;
        }

        $friendList[] = $entry;
    }

    usort($friendList, function ($a, $b) {
        $diff = $b['LastSeenTime'] - $a['LastSeenTime'];
        if ($diff === 0) {
            $diff = $b['RAPoints'] - $a['RAPoints'];
        }

        return $diff;
    });

    return $friendList;
}

function GetExtendedFriendsList(User $user): array
{
    $friendList = $user->followedUsers()
        ->where('Permissions', '>=', Permissions::Unregistered)
        ->whereNull('Deleted')
        ->orderBy('RichPresenceMsgDate', 'DESC')
        ->get()
        ->map(function ($friend) {
            return [
                'User' => $friend->display_name,
                'Friendship' => (int) $friend->pivot->Friendship,
                'LastGameID' => (int) $friend->LastGameID,
                'LastSeen' => empty($friend->RichPresenceMsg) ? 'Unknown' : strip_tags($friend->RichPresenceMsg),
                'LastActivityTimestamp' => $friend->RichPresenceMsgDate?->format('Y-m-d H:i:s'),
            ];
        });

    return $friendList->toArray();
}

function GetFriendsSubquery(string $user, bool $includeUser = true, bool $returnUserIds = false): string
{
    $userModel = User::whereName($user)->first();
    $userId = $userModel->id;

    $selectColumn = $returnUserIds ? 'ua.ID' : 'ua.User';

    $friendsSubquery = "SELECT $selectColumn FROM UserAccounts ua
        JOIN (
            SELECT related_user_id FROM Friends
            WHERE user_id = :userId AND Friendship = :friendshipStatus
        ) AS Friends1 ON Friends1.related_user_id = ua.ID
        WHERE ua.Deleted IS NULL AND ua.Permissions >= :permissionsLevel
    ";

    $bindings = [
        'userId' => $userId,
        'friendshipStatus' => UserRelationship::Following,
        'permissionsLevel' => Permissions::Unregistered,
    ];

    // TODO: why is it so much faster to run this query and build the IN list
    //       than to use it as a subquery? i.e. "AND aw.User IN ($friendsSubquery)"
    //       local testing took over 2 seconds with the subquery and < 0.01 seconds
    //       total for two separate queries
    $friends = [];
    foreach (legacyDbFetchAll($friendsSubquery, $bindings) as $db_entry) {
        $friends[] = $returnUserIds ? $db_entry['ID'] : "'" . $db_entry['User'] . "'";
    }

    if ($includeUser) {
        $friends[] = $returnUserIds ? $userId : "'$user'";
    } elseif (count($friends) == 0) {
        return "NULL";
    }

    return implode(',', $friends);
}

/**
 * Gets the number of friends for the input user.
 */
function getFriendCount(?User $user): int
{
    if (!$user) {
        return 0;
    }

    return $user->followedUsers()->count();
}
