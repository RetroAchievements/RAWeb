<?php

use RA\FriendshipType;
use RA\Permissions;
use RA\UserPreference;

function changeFriendStatus(string $user, string $friend, int $newStatus): string
{
    sanitize_sql_inputs($user, $friend);

    $oldStatus = GetFriendship($user, $friend);
    if ($oldStatus == $newStatus) {
        return "nochange";
    }

    // TODO: figure out how to do this without using an extra query...
    //       INSERT.. ON DUPLICATE KEY doesn't work because the user/friend combination is not a unique key
    $query = "SELECT Friendship FROM Friends WHERE User='$user' AND Friend='$friend'";
    $dbresult = s_mysql_query($query);
    if (!$dbresult) {
        return "error";
    }

    if (mysqli_num_rows($dbresult) == 0) {
        $query = "INSERT INTO Friends (User, Friend, Friendship) VALUES ('$user', '$friend', $newStatus)";
    } else {
        $query = "UPDATE Friends SET Friendship=$newStatus WHERE User='$user' AND Friend='$friend'";
    }
    $dbresult = s_mysql_query($query);
    if (!$dbresult) {
        return "error";
    }

    switch ($newStatus) {
        case FriendshipType::Friend:
            if ($oldStatus == FriendshipType::Impossible) {
                // other user has blocked this user, can't friend them
                return "error";
            }

            if ($oldStatus == FriendshipType::Requested) {
                // confirming friendship
                if (getAccountDetails($friend, $friendData)) {
                    if (BitSet($friendData['websitePrefs'], UserPreference::EmailOn_AddFriend)) {
                        // notify the friend of the mutual friendship
                        sendFriendEmail($friend, $friendData['EmailAddress'], 1, $user);
                    }
                }

                return "friendconfirmed";
            }

            // NotFriend, Blocked, Pending
            // requesting friendship
            if (getAccountDetails($friend, $friendData)) {
                if (BitSet($friendData['websitePrefs'], UserPreference::EmailOn_AddFriend)) {
                    // notify the new friend of the request
                    sendFriendEmail($friend, $friendData['EmailAddress'], 0, $user);
                }
            }

            return "friendrequested";

        case FriendshipType::NotFriend:
            if ($oldStatus != FriendshipType::Impossible) {
                // if the other user hasn't blocked the user, clear out their friendship status too
                $query = "UPDATE Friends SET Friendship=$newStatus WHERE User='$friend' AND Friend='$user'";
                $dbResult = s_mysql_query($query);
            }

            return match ($oldStatus) {
                FriendshipType::Friend => "friendremoved",
                FriendshipType::Blocked => "friendunblocked",
                FriendshipType::Pending => "friendrequestcanceled",
                FriendshipType::Requested => "friendrequestdeclined",
                default => "error",
            };

        case FriendshipType::Blocked:
            if ($oldStatus != FriendshipType::Impossible) {
                // if the other user hasn't blocked the user, clear out their friendship status too
                $query = "UPDATE Friends SET Friendship=" . FriendshipType::NotFriend . " WHERE User='$friend' AND Friend='$user'";
                $dbResult = s_mysql_query($query);
            }

            return "friendblocked";

        default:
            return "error";
    }
}

function isUserBlocking($user, $possibly_blocked_user): bool
{
    sanitize_sql_inputs($user, $possibly_blocked_user);

    $query = "SELECT * FROM Friends WHERE User='$user' AND Friend='$possibly_blocked_user'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    if (!$data) {
        return false;
    }

    return (int) $data['Friendship'] == FriendshipType::Blocked;
}

function getAllFriendsProgress($user, $gameID, &$friendScoresOut): int
{
    sanitize_sql_inputs($user, $gameID);

    $friendScoresOut = [];
    // Subquery one: select all friends this user has added:
    // Subquery two: select all achievements associated with this game:

    // Manual sanitisation, as we need to call multiple functions (and include semicolons)
    settype($gameID, 'integer');
    if (!ctype_alnum($user)) {
        return 0;
    }

    $friendSubquery = GetFriendsSubquery($user, false);

    // Less dependent subqueries :)
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
            ) AS aw 
            LEFT JOIN UserAccounts AS ua ON ua.User = aw.User 
            LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID 
            LEFT JOIN Activity AS act ON act.ID = ua.LastActivityID 
            WHERE aw.User IN ( $friendSubquery )
            GROUP BY aw.User 
            ORDER BY TotalPoints DESC, aw.User";

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

function GetFriendsSubquery(string $user, bool $includeUser = true)
{
    $friendsSubquery = "SELECT ua.User FROM UserAccounts ua
         JOIN (SELECT Friend AS User FROM Friends WHERE User='$user' AND Friendship=" . FriendshipType::Friend . ") as Friends1 ON Friends1.User=ua.User
         JOIN (SELECT User FROM Friends WHERE Friend='$user' AND Friendship=" . FriendshipType::Friend . ") as Friends2 ON Friends2.User=Friends1.User
         WHERE ua.Deleted IS NULL AND ua.Permissions >= " . Permissions::Unregistered;

    // TODO: why is it so much faster to run this query and build the IN list
    //       than to use it as a subquery? i.e. "AND aw.User IN ($friendsSubquery)"
    //       local testing took over 2 seconds with the subquery and < 0.01 seconds
    //       total for two separate queries
    $friends = [];
    $dbResult = s_mysql_query($friendsSubquery);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $friends[] = "'" . $db_entry['User'] . "'";
        }
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

    $filter1 = "f.User='$user'";
    $filter2 = "f.Friend='$user'";
    if ($possibleFriend != null) {
        $filter1 .= " AND f.Friend='$possibleFriend'";
        $filter2 .= " AND f.User='$possibleFriend'";
    }

    $friendList = [];

    $query = "SELECT f.Friend AS User, 1 AS LocalFriend, f.Friendship, ua.LastGameID, ua.RichPresenceMsg AS LastSeen
              FROM Friends AS f
              JOIN UserAccounts AS ua ON ua.User = f.Friend
              WHERE $filter1
              AND ua.Permissions >= " . Permissions::Unregistered . " AND ua.Deleted IS NULL
              UNION
              SELECT f.User AS User, 0 AS LocalFriend, f.Friendship, ua.LastGameID, ua.RichPresenceMsg AS LastSeen
              FROM Friends AS f
              JOIN UserAccounts AS ua ON ua.User = f.User
              WHERE $filter2
              AND ua.Permissions >= " . Permissions::Unregistered . " AND ua.Deleted IS NULL
              ORDER BY User, LocalFriend DESC";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();
    } else {
        $lastFriend = "";
        $lastFriendship = FriendshipType::NotFriend;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['Friendship'], 'int');

            if ($db_entry['LocalFriend'] == 0) {
                if ($db_entry['User'] == $lastFriend) {
                    switch ($db_entry['Friendship']) {
                        case FriendshipType::Blocked:
                            // other user has blocked us, current user has no action
                            $friendList[array_key_last($friendList)]['Friendship'] = FriendshipType::Impossible;
                            break;
                        case FriendshipType::NotFriend:
                            // other user has not friended us
                            // local Friend => Pending
                            // local Blocked => Blocked
                            // local NotFriend => NotFriend (safe to discard)
                            if ($lastFriendship == FriendshipType::Friend) {
                                $friendList[array_key_last($friendList)]['Friendship'] = FriendshipType::Pending;
                            } elseif ($lastFriendship == FriendshipType::NotFriend) {
                                array_pop($friendList);
                            }
                            break;
                        case FriendshipType::Friend:
                            // other user has friended us
                            // local Pending => Friend
                            // local Blocked => Blocked
                            // local NotFriend => Requested
                            if ($lastFriendship == FriendshipType::NotFriend) {
                                $friendList[array_key_last($friendList)]['Friendship'] = FriendshipType::Requested;
                            } elseif ($lastFriendship == FriendshipType::Pending) {
                                $friendList[array_key_last($friendList)]['Friendship'] = FriendshipType::Friend;
                            }
                            break;
                    }
                    continue;
                } else {
                    // other user has friendship data, but we don't. translate to our perspective
                    // local NotFriend, other Friend => Requested
                    // local NotFriend, other Blocked => NotFriend (safe to discard)
                    // local NotFriend, other NotFriend => NotFriend (safe to discard)
                    if ($db_entry['Friendship'] == FriendshipType::Friend) {
                        $db_entry['Friendship'] = FriendshipType::Requested;
                    } else {
                        continue;
                    }
                }
            }

            $lastFriend = $db_entry['User'];
            $lastFriendship = $db_entry['Friendship'];

            if ($db_entry['Friendship'] == FriendshipType::Friend) {
                // set to pending until we see the reciprocal relationship
                $db_entry['Friendship'] = $lastFriendship = FriendshipType::Pending;
            }

            $db_entry["LastSeen"] = empty($db_entry["LastSeen"]) ? "Unknown" : strip_tags($db_entry["LastSeen"]);
            $friendList[] = $db_entry;
        }
    }

    return $friendList;
}

function GetFriendship(string $user, string $possibleFriend): int
{
    foreach (GetExtendedFriendsList($user, $possibleFriend) as $friend) {
        if ($friend['User'] == $possibleFriend) {
            return $friend['Friendship'];
        }
    }

    return FriendshipType::NotFriend;
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
              FROM Friends
              WHERE User LIKE '$user'
              AND Friendship = 1";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['FriendCount'];
}
