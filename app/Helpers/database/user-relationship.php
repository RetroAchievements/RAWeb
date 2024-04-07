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

    if ($newStatus === UserRelationship::Following && isUserBlocking($targetUser->User, $senderUser->User)) {
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
            if (!isUserBlocking($targetUser->User, $senderUser->User)) {
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

function isUserBlocking(string $user, ?string $possibly_blocked_user): bool
{
    if (!isset($possibly_blocked_user)) {
        return false;
    }

    return GetFriendship($user, $possibly_blocked_user) == UserRelationship::Blocked;
}

function GetFriendship(string $user, string $friend): int
{
    sanitize_sql_inputs($user, $friend);

    $query = "SELECT Friendship FROM Friends WHERE User='$user' AND Friend='$friend'";
    $dbresult = s_mysql_query($query);
    if ($dbresult) {
        $data = mysqli_fetch_assoc($dbresult);
        if ($data) {
            return (int) $data['Friendship'];
        }
    }

    return UserRelationship::NotFollowing;
}

function GetFriendList(string $user): array
{
    sanitize_sql_inputs($user);

    $friendList = [];

    $friendSubquery = GetFriendsSubquery($user, false);
    $query = "SELECT ua.User as Friend, ua.RAPoints, ua.RichPresenceMsg AS LastSeen, ua.ID
              FROM UserAccounts ua
              WHERE ua.User IN ( $friendSubquery )
              ORDER BY ua.LastActivityID DESC";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    } else {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $db_entry["LastSeen"] = empty($db_entry["LastSeen"]) || $db_entry['LastSeen'] === 'Unknown' ? "_" : strip_tags($db_entry["LastSeen"]);

            $friendList[] = $db_entry;
        }
    }

    return $friendList;
}

function GetFriendsSubquery(string $user, bool $includeUser = true): string
{
    $friendsSubquery = "SELECT ua.User FROM UserAccounts ua
         JOIN (SELECT Friend AS User FROM Friends WHERE User=:user AND Friendship=" . UserRelationship::Following . ") as Friends1 ON Friends1.User=ua.User
         WHERE ua.Deleted IS NULL AND ua.Permissions >= " . Permissions::Unregistered;

    // TODO: why is it so much faster to run this query and build the IN list
    //       than to use it as a subquery? i.e. "AND aw.User IN ($friendsSubquery)"
    //       local testing took over 2 seconds with the subquery and < 0.01 seconds
    //       total for two separate queries
    $friends = [];
    foreach (legacyDbFetchAll($friendsSubquery, ['user' => $user]) as $db_entry) {
        $friends[] = "'" . $db_entry['User'] . "'";
    }

    if ($includeUser) {
        $friends[] = "'$user'";
    } elseif (count($friends) == 0) {
        return "NULL";
    }

    return implode(',', $friends);
}

function GetExtendedFriendsList(string $user, ?string $possibleFriend = null): array
{
    sanitize_sql_inputs($user);

    $friendList = [];

    $query = "SELECT f.Friend AS User, f.Friendship, ua.LastGameID, ua.RichPresenceMsg AS LastSeen, ua.RichPresenceMsgDate as LastActivityTimestamp
              FROM Friends AS f
              JOIN UserAccounts AS ua ON ua.User = f.Friend
              WHERE f.User='$user'
              AND ua.Permissions >= " . Permissions::Unregistered . " AND ua.Deleted IS NULL
              ORDER BY LastActivityTimestamp DESC";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    } else {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $db_entry['Friendship'] = (int) $db_entry['Friendship'];
            $db_entry['LastGameID'] = (int) $db_entry['LastGameID'];

            $db_entry["LastSeen"] = empty($db_entry["LastSeen"]) ? "Unknown" : strip_tags($db_entry["LastSeen"]);
            $friendList[] = $db_entry;
        }
    }

    return $friendList;
}

/**
 * Gets the number of friends for the input user.
 */
function getFriendCount(?string $user): int
{
    sanitize_sql_inputs($user);

    if (!$user) {
        return 0;
    }

    $query = "SELECT COUNT(*) AS FriendCount
              FROM Friends AS f
              JOIN UserAccounts AS ua ON ua.User=f.Friend
              WHERE f.User LIKE '$user'
              AND f.Friendship = " . UserRelationship::Following . " AND ua.Deleted IS NULL
              AND ua.Permissions >= " . Permissions::Unregistered;

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['FriendCount'];
}

function GetFollowers(string $user): array
{
    sanitize_sql_inputs($user);

    $followers = [];

    $query = "SELECT f.User
              FROM Friends AS f
              JOIN UserAccounts AS ua ON ua.User = f.User
              WHERE f.Friend='$user' AND f.Friendship=" . UserRelationship::Following . "
              AND ua.Permissions >= " . Permissions::Unregistered . " AND ua.Deleted IS NULL
              ORDER BY f.User";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    } else {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $followers[] = $db_entry['User'];
        }
    }

    return $followers;
}
