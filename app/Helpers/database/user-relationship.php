<?php

use App\Community\Enums\UserRelationship;
use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Models\User;
use App\Models\UserRelation;

function changeFriendStatus(User $senderUser, User $targetUser, int $newStatus): string
{
    $existingUserRelation = UserRelation::where('User', $senderUser->User)
        ->where('Friend', $targetUser->User)
        ->first();

    $newRelationship = false;
    if ($existingUserRelation) {
        $oldStatus = $existingUserRelation->Friendship;
    } else {
        $newRelationship = true;
        $oldStatus = UserRelationship::NotFollowing;
    }

    if ($newStatus === UserRelationship::Following && $targetUser->isBlocking($senderUser)) {
        // other user has blocked this user, can't follow them
        return "error";
    }

    // Upsert the relationship.
    if ($existingUserRelation) {
        $existingUserRelation->Friendship = $newStatus;
        $existingUserRelation->save();
    } else {
        UserRelation::create([
            'User' => $senderUser->User,
            'user_id' => $senderUser->id,
            'Friend' => $targetUser->User,
            'related_user_id' => $targetUser->id,
            'Friendship' => $newStatus,
        ]);
    }

    switch ($newStatus) {
        case UserRelationship::Following:
            // attempt to notify the target of the new follower
            if ($newRelationship && BitSet($targetUser->websitePrefs, UserPreference::EmailOn_Followed)) {
                // notify the new friend of the request
                sendFriendEmail($targetUser->User, $targetUser->EmailAddress, 0, $senderUser->User);
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
                UserRelation::where('User', $targetUser->User)
                    ->where('Friend', $senderUser->User)
                    ->update(['Friendship' => UserRelationship::NotFollowing]);
            }

            return "user_block";

        default:
            return "error";
    }
}

function GetFriendList(User $user): array
{
    $friendList = $user->followedUsers()
        ->where('Permissions', '>=', Permissions::Unregistered)
        ->whereNull('Deleted')
        ->orderBy('LastActivityID', 'DESC')
        ->get()
        ->map(function ($friend) {
            return [
                'Friend' => $friend->User,
                'RAPoints' => $friend->points,
                'LastSeen' => empty($friend->RichPresenceMsg) ? 'Unknown' : strip_tags($friend->RichPresenceMsg),
                'ID' => $friend->id,
            ];
        });

    return $friendList->toArray();
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
                'User' => $friend->User,
                'Friendship' => (int) $friend->pivot->Friendship,
                'LastGameID' => (int) $friend->LastGameID,
                'LastSeen' => empty($friend->RichPresenceMsg) ? 'Unknown' : strip_tags($friend->RichPresenceMsg),
                'LastActivityTimestamp' => $friend->RichPresenceMsgDate?->format('Y-m-d H:i:s'),
            ];
        });

    return $friendList->toArray();
}

function GetFriendsSubquery(string $user, bool $includeUser = true): string
{
    $userModel = User::firstWhere('User', $user);
    $userId = $userModel->id;

    $friendsSubquery = "SELECT ua.User FROM UserAccounts ua
        JOIN (
            SELECT related_user_id FROM Friends
            WHERE user_id=:userId AND Friendship = :friendshipStatus
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
        $friends[] = "'" . $db_entry['User'] . "'";
    }

    if ($includeUser) {
        $friends[] = "'$user'";
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
