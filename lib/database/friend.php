<?php

use RA\AwardedHardcoreMode;

function changeFriendStatus($user, $friend, $action): string
{
    sanitize_sql_inputs($user, $friend, $action);
    settype($action, 'integer');

    $query = "SELECT (f.User = '$user') AS Local, f.Friend, f.Friendship
              FROM Friends AS f
              WHERE (f.User = '$user' && f.Friend = '$friend')
              UNION
              SELECT (f.User = '$user') AS Local, f.Friend, f.Friendship
              FROM Friends AS f
              WHERE (f.User = '$friend' && f.Friend = '$user') ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $localFriendState = null;
        $remoteFriendState = null;
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if ($data['Local'] == 1) {
                $localFriendState = $data['Friendship'];
                settype($localFriendState, 'integer');
            } else { // if( $data['Local'] == 0 )
                $remoteFriendState = $data['Friendship'];
                settype($remoteFriendState, 'integer');
            }
        }

        if (!isset($localFriendState)) {
            // Entry needs adding afresh:
            $query = "INSERT INTO Friends (User, Friend, Friendship) VALUES ( '$user', '$friend', $action )";
            $dbResult = s_mysql_query($query);

            if ($dbResult !== false) {
                if (isset($remoteFriendState)) {
                    // Friend already has an entry about us.

                    if ($remoteFriendState == 1) {
                        // remote friend is already friends: they sent the request perhaps?
                        if (getAccountDetails($friend, $friendData)) {
                            if (($friendData['websitePrefs'] & (1 << 4)) !== 0) {
                                // Note reverse user and friend (perspective!)
                                sendFriendEmail($friend, $friendData['EmailAddress'], 1, $user);
                            }
                        }

                        return "friendconfirmed";
                    } elseif ($remoteFriendState == 0) {
                        // remote friend has an entry for this person, but they are not friends.
                        return "friendadded";
                    } elseif ($remoteFriendState == -1) {
                        // remote friend has blocked this user.
                        return "error";
                    }
                } else {
                    // Remote friend hasn't heard about us yet!
                    if ($action == 1) {
                        // Notify $friend that $user wants to be their friend

                        if (getAccountDetails($friend, $friendData)) {
                            if (($friendData['websitePrefs'] & (1 << 4)) !== 0) {
                                // Note reverse user and friend (perspective!)
                                sendFriendEmail($friend, $friendData['EmailAddress'], 0, $user);
                            }
                        }

                        return "friendrequested";
                    }
                }
            } else {
                log_sql_fail();
                return "issues1";
            }
        } else { // if( isset( $localFriendState ) )
            // My entry already exists in some form.
            if ($localFriendState == $action) {
                // No change:
                return "nochange";
            } else {
                // Entry exists already but needs changing to $action:

                $query = "UPDATE Friends AS f SET f.Friendship = $action ";
                $query .= "WHERE f.User = '$user' AND f.Friend = '$friend' ";
                $dbResult = s_mysql_query($query);

                if ($dbResult !== false) {
                    if ($localFriendState == -1 && $action == 0) {
                        return "userunblocked";
                    }
                    if ($action == 0) {
                        return "friendremoved";
                    } elseif ($action == -1) {
                        return "friendblocked";
                    } elseif ($action == 1) {
                        // Notify $friend that $user confirmed their friend request!
                        if (isset($remoteFriendState) && $remoteFriendState == 1) {
                            return "friendconfirmed";    // again
                        } else {
                            return "friendrequested";    // again
                        }
                    }
                } else {
                    log_sql_fail();
                    return "issues2";
                }
            }
        }
    } else {
        log_sql_fail();
        return "sqlfail";
    }

    return "error";
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

    return $data['Friendship'] == '-1';
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
                WHERE aw.HardcoreMode = " . AwardedHardcoreMode::Hardcore . "
            ) AS aw 
            NATURAL JOIN 
            ( 
                SELECT f.Friend 
                FROM Friends AS f 
                WHERE f.User = '$user'
                AND f.Friendship = 1
            ) AS _FriendList 
            LEFT JOIN UserAccounts AS ua ON ua.User = aw.User 
            LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID 
            LEFT JOIN Activity AS act ON act.ID = ua.LastActivityID 
            WHERE aw.User = _FriendList.Friend 
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

function GetFriendList($user): array
{
    sanitize_sql_inputs($user);

    $friendList = [];

    $query = "SELECT f.Friend, ua.RAPoints, ua.RichPresenceMsg AS LastSeen, ua.ID
              FROM Friends AS f
              LEFT JOIN UserAccounts AS ua ON ua.User = f.Friend
              WHERE f.User='$user'
              AND f.Friendship = 1
              AND ua.ID IS NOT NULL
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
