<?php
function changeFriendStatus($user, $friend, $action)
{
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
            //var_dump( $data );
            if ($data['Local'] == 1) {
                $localFriendState = $data['Friendship'];
                settype($localFriendState, 'integer');
            } else { //if( $data['Local'] == 0 )
                $remoteFriendState = $data['Friendship'];
                settype($remoteFriendState, 'integer');
            }
        }

        if (!isset($localFriendState)) {
            //    Entry needs adding afresh:
            $query = "INSERT INTO Friends (User, Friend, Friendship) VALUES ( '$user', '$friend', $action )";
            // log_sql($query);
            $dbResult = s_mysql_query($query);

            if ($dbResult !== false) {
                if (isset($remoteFriendState)) {
                    //    Friend already has an entry about us.

                    if ($remoteFriendState == 1) {
                        //    remote friend is already friends: they sent the request perhaps?
                        if (getAccountDetails($friend, $friendData)) {
                            if (($friendData['websitePrefs'] & (1 << 4)) !== 0) {
                                //    Note reverse user and friend (perspective!)
                                sendFriendEmail($friend, $friendData['EmailAddress'], 1, $user);
                            } else {
                                // error_log(__FUNCTION__ . " not sending $friend any email about this friend confirm, they don't want emails.");
                            }
                        }

                        return "friendconfirmed";
                    } elseif ($remoteFriendState == 0) {
                        //    remote friend has an entry for this person, but they are not friends.
                        return "friendadded";
                    } elseif ($remoteFriendState == -1) {
                        //    remote friend has blocked this user.
                        return "error";
                    }
                } else {
                    //    Remote friend hasn't heard about us yet!
                    if ($action == 1) {
                        //    Notify $friend that $user wants to be their friend

                        if (getAccountDetails($friend, $friendData)) {
                            if (($friendData['websitePrefs'] & (1 << 4)) !== 0) {
                                //    Note reverse user and friend (perspective!)
                                sendFriendEmail($friend, $friendData['EmailAddress'], 0, $user);
                            } else {
                                // error_log(__FUNCTION__ . " friend request, $friend has elected not to have email about $user adding them as a friend");
                            }
                        } else {
                            // error_log(__FUNCTION__ . " friend request, cannot get friend data!");
                        }

                        return "friendrequested";
                    }
                }
            } else {
                log_sql_fail();
                return "issues1";
            }
        } else { //if( isset( $localFriendState ) )
            //    My entry already exists in some form.
            if ($localFriendState == $action) {
                //    No change:
                return "nochange";
            } else {
                //    Entry exists already but needs changing to $action:

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
                        //    Notify $friend that $user confirmed their friend request!
                        if (isset($remoteFriendState) && $remoteFriendState == 1) {
                            return "friendconfirmed";    //    again
                        } else {
                            return "friendrequested";    //    again
                        }
                    }
                } else {
                    log_sql_fail();
                    return "issues2";
                }
            }
        }
    } else {
        // error_log(__FUNCTION__);
        log_sql_fail();
        return "sqlfail";
    }
}

function addFriend($user, $friendToAdd)
{
    $query = "SELECT * FROM Friends WHERE (User='$user' AND Friend='$friendToAdd') OR (User='$friendToAdd' AND Friend='$user')";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $numRows = mysqli_num_rows($dbResult);
        if ($numRows == 0) {
            //    New friend request
            //    Add as a confirmed friend for me
            $query = "INSERT INTO Friends (User, Friend, Friendship) VALUES ( '$user', '$friendToAdd', '1' )";
            // log_sql($query);
            $dbResult = s_mysql_query($query);
            if ($dbResult == false) {
                log_sql_fail();
            }

            //    Add as a pending friend for him
            $query = "INSERT INTO Friends (User, Friend, Friendship) VALUES ( '$friendToAdd', '$user', '0' )";
            // log_sql($query);
            $dbResult = s_mysql_query($query);
            if ($dbResult == false) {
                log_sql_fail();
            }

            return true;
        } else {
            //    Friend request already sent. To simply this, just fail, call "confirmFriend" instead!
            // error_log(__FUNCTION__ . " failed: friend request already sent from user:$user, friend to add:$friendToAdd (numRows: $numRows)");
            return false;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: friend request query failed:$user, friend to add:$friendToAdd");
        return false;
    }
}

function confirmFriend($user, $friendToConfirm)
{
    $query = "SELECT * FROM Friends WHERE User='$user' AND Friend='$friendToConfirm'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $numRows = mysqli_num_rows($dbResult);
        if ($numRows > 1) {
            // error_log(__FUNCTION__ . " warning: something's screwed up, $user has more than 1 request to confirm from $friendToAdd");
        }

        if ($numRows == 1) {
            $query = "UPDATE Friends SET Friendship='1' WHERE User='$user' AND Friend='$friendToConfirm'";
            if (s_mysql_query($query) !== false) {
                //    Accepted successfully :)
                return true;
            } else {
                log_sql_fail();
                // error_log(__FUNCTION__ . "query failed: user:$user, friend:$friendToConfirm");
                return false;
            }
        } else {
            // error_log(__FUNCTION__ . " failed: no friendship to confirm? User:$user Friend:$friendToConfirm");
            return false;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: friend request query failed:$user, friend to add:$friendToAdd");
        return false;
    }
}

function blockFriend($user, $friendToConfirm)
{
    $query = "SELECT * FROM Friends WHERE User='$user' AND Friend='$friendToConfirm'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        $numRows = mysqli_num_rows($dbResult);
        if ($numRows > 1) {
            // error_log(__FUNCTION__ . " warning: something's screwed up, $user has more than 1 entry with $friendToConfirm");
        }

        if ($numRows == 1) {
            $query = "UPDATE Friends SET Friendship='-1' WHERE User='$user' AND Friend='$friendToConfirm'";
            if (s_mysql_query($query) !== false) {
                //    Accepted successfully :)
                return true;
            } else {
                log_sql_fail();
                // error_log(__FUNCTION__ . "query failed: user:$user, friend:$friendToConfirm");
                return false;
            }
        } else {
            // error_log(__FUNCTION__ . " failed: no friendship to confirm? User:$user Friend:$friendToConfirm");
            return false;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: friend request query failed:$user, friend to add:$friendToConfirm");
        return false;
    }
}

function isFriendsWith($user, $friend)
{
    $query = "SELECT * FROM Friends WHERE User='$user' AND Friend='$friend'";
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    if ($data == false) {
        return false;
    }

    return $data['Friendship'] == '1';
}

function getAllFriendsProgress($user, $gameID, &$friendScoresOut)
{
    $friendScoresOut = [];
    //    Subquery one: select all friends this user has added:
    //    Subquery two: select all achievements associated with this game:

    //    Manual sanitisation, as we need to call multiple functions (and include semicolons)
    settype($gameID, 'integer');
    if (!ctype_alnum($user)) {
        // error_log(__FUNCTION__ . " called with dodgy looking user: $user");
        //log_email(__FUNCTION__ . "failed... user is $user");
        return 0;
    }

    //s_mysql_query( "CREATE VIEW _FriendList AS SELECT f.Friend FROM Friends as f WHERE f.User = '$user'" );
    //s_mysql_query( "CREATE VIEW _ThisAwarded AS SELECT aw.User, aw.AchievementID, aw.Date FROM Awarded AS aw WHERE aw.AchievementID IN ( SELECT ID FROM Achievements WHERE Achievements.GameID = '$gameID' )" );

    //$query      = "SELECT aw.User, ua.Motto, SUM( ach.Points ) AS TotalPoints, ua.RAPoints, act.LastUpdate
    //            FROM _ThisAwarded AS aw
    //            NATURAL JOIN _FriendList
    //            LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
    //            LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
    //            LEFT JOIN Activity AS act ON act.ID = ua.LastActivityID
    //            WHERE aw.User = _FriendList.Friend
    //            GROUP BY aw.User
    //            ORDER BY TotalPoints DESC, aw.User";

    //    Concatenated queries:
    // $query = "SELECT aw.User, ua.Motto, SUM( ach.Points ) AS TotalPoints, ua.RAPoints, act.LastUpdate
    // FROM (
    // SELECT aw.User, aw.AchievementID, aw.Date FROM Awarded AS aw WHERE aw.AchievementID IN
    // ( SELECT ID FROM Achievements WHERE Achievements.GameID = '$gameID' )
    // ) AS aw
    // NATURAL JOIN ( SELECT f.Friend FROM Friends as f WHERE f.User = '$user' ) AS _FriendList
    // LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
    // LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
    // LEFT JOIN Activity AS act ON act.ID = ua.LastActivityID
    // WHERE aw.User = _FriendList.Friend
    // GROUP BY aw.User
    // ORDER BY TotalPoints DESC, aw.User ";

    //    Less dependent subqueries :)
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
            NATURAL JOIN 
            ( 
                SELECT f.Friend 
                FROM Friends AS f 
                WHERE f.User = '$user'
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

            //    Tally up our friend's scores
            $friendScoresOut[$db_entry['User']] = $db_entry;
            $numFriends++;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed3: user:$user gameID:$gameID");
    }

    //s_mysql_query( "DROP VIEW _FriendList" );
    //s_mysql_query( "DROP VIEW _ThisAwarded" );

    return $numFriends;
}

function GetFriendList($user)
{
    $friendList = [];

    $query = "SELECT f.Friend, ua.RAPoints, ua.RichPresenceMsg AS LastSeen, ua.ID
              FROM Friends AS f
              LEFT JOIN UserAccounts AS ua ON ua.User = f.Friend
              WHERE f.User='$user'
              AND ua.ID IS NOT NULL
              ORDER BY ua.LastActivityID DESC";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: user:$user");
    } else {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $db_entry["LastSeen"] = empty($db_entry["LastSeen"]) || $db_entry['LastSeen'] === 'Unknown' ? "_" : strip_tags($db_entry["LastSeen"]);

            $friendList[] = $db_entry;
        }
    }

    return $friendList;
}

// function getLastKnownActivity( $user )
// {
//     $query = "SELECT act.ID, act.timestamp, act.activitytype, act.User, act.data, act.data2
//         FROM UserAccounts AS ua
//         LEFT JOIN Activity AS act ON act.ID = ua.LastActivityID
//         WHERE ua.User = '$user'";
//     $dbResult = s_mysql_query( $query );
//     return mysqli_fetch_assoc( $dbResult );
// }
