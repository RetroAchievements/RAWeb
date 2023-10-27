<?php

use App\Community\Enums\UserRelationship;
use App\Platform\Enums\UnlockMode;
use App\Site\Enums\Permissions;
use App\Site\Enums\UserPreference;

function changeFriendStatus(string $user, string $friend, int $newStatus): string
{
    sanitize_sql_inputs($user, $friend);

    $query = "SELECT Friendship FROM Friends WHERE User='$user' AND Friend='$friend'";
    $dbresult = s_mysql_query($query);
    if (!$dbresult) {
        log_sql_fail();

        return "error";
    }

    $newRelationship = false;
    $data = mysqli_fetch_assoc($dbresult);
    if ($data) {
        $oldStatus = (int) $data['Friendship'];
        $query = "UPDATE Friends SET Friendship=$newStatus WHERE User='$user' AND Friend='$friend'";
    } else {
        $newRelationship = true;
        $oldStatus = UserRelationship::NotFollowing;
        $query = "INSERT INTO Friends (User, Friend, Friendship) VALUES ('$user', '$friend', $newStatus)";
    }

    if ($oldStatus === $newStatus) {
        return "nochange";
    }

    if ($newStatus == UserRelationship::Following && isUserBlocking($friend, $user)) {
        // other user has blocked this user, can't follow them
        return "error";
    }

    $dbresult = s_mysql_query($query);
    if (!$dbresult) {
        return "error";
    }

    switch ($newStatus) {
        case UserRelationship::Following:
            // attempt to notify the target of the new follower
            $friendData = [];
            if (getAccountDetails($friend, $friendData)) {
                if ($newRelationship && BitSet($friendData['websitePrefs'], UserPreference::EmailOn_Followed)) {
                    // notify the new friend of the request
                    sendFriendEmail($friend, $friendData['EmailAddress'], 0, $user);
                }
            }

            return "user_follow";

        case UserRelationship::NotFollowing:
            return match ($oldStatus) {
                UserRelationship::Following => "user_unfollow",
                UserRelationship::Blocked => "user_unblock",
                default => "error",
            };

        case UserRelationship::Blocked:
            if (!isUserBlocking($friend, $user)) {
                // if the other user hasn't blocked the user, clear out their friendship status too
                $query = "UPDATE Friends SET Friendship=" . UserRelationship::NotFollowing . " WHERE User='$friend' AND Friend='$user'";
                $dbResult = s_mysql_query($query);
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

function getAllFriendsProgress(string $user, int $gameID, ?array &$friendScoresOut): int
{
    sanitize_sql_inputs($user);

    $friendScoresOut = [];
    // Subquery one: select all friends this user has added:
    // Subquery two: select all achievements associated with this game:

    if (!isValidUsername($user)) {
        return 0;
    }

    $friendSubquery = GetFriendsSubquery($user, false);

    // Less dependent subqueries :)
    if (config('feature.aggregate_queries')) {
        $query = "SELECT ua.User, ua.Motto, ua.RAPoints, ua.RichPresenceMsg,
                         pg.points_hardcore AS TotalPoints
                  FROM player_games pg
                  LEFT JOIN UserAccounts ua ON ua.ID = pg.user_id
                  WHERE ua.User IN ( $friendSubquery ) AND pg.game_id = $gameID
                  AND pg.points_hardcore > 0
                  ORDER BY TotalPoints DESC, ua.User";
    } else {
        $query = "SELECT aw.User, ua.Motto, SUM( ach.Points ) AS TotalPoints, ua.RAPoints, ua.RichPresenceMsg, act.LastUpdate
                FROM
                (
                    SELECT aw.User, aw.AchievementID, aw.Date
                    FROM Awarded AS aw
                    RIGHT JOIN
                    (
                        SELECT ID
                        FROM Achievements AS ach
                        WHERE ach.GameID = '$gameID' AND ach.Flags = 3
                    ) AS Inner1 ON Inner1.ID = aw.AchievementID
                    WHERE aw.HardcoreMode = " . UnlockMode::Hardcore . "
                ) AS aw
                LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                LEFT JOIN Activity AS act ON act.ID = ua.LastActivityID
                WHERE aw.User IN ( $friendSubquery )
                GROUP BY aw.User
                ORDER BY TotalPoints DESC, aw.User";
    }

    $dbResult = s_mysql_query($query);

    $numFriends = 0;

    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            if (!isset($friendScoresOut[$db_entry['User']])) {
                $friendScoresOut[$db_entry['User']] = 0;
            }

            // Tally up our friend's scores
            $friendScoresOut[$db_entry['User']] = $db_entry;
            $numFriends++;
        }
    } else {
        log_sql_fail();
    }

    return $numFriends;
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
