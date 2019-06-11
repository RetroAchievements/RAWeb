<?php

use RA\ActivityType;
use RA\ObjectType;

require_once(__DIR__ . '/../bootstrap.php');
//////////////////////////////////////////////////////////////////////////////////////////
//    Activity/Feed
//////////////////////////////////////////////////////////////////////////////////////////
//    01:00 23/02/2013
function getMostRecentActivity($user, $type, $data)
{
    $innerClause = "Activity.user = '$user'";
    if (isset($type)) {
        $innerClause .= " AND Activity.activityType = $type";
    }
    if (isset($data)) {
        $innerClause .= " AND Activity.data = $data";
    }

    $query = "SELECT * FROM Activity AS act
              WHERE act.ID =
                ( SELECT MAX(Activity.ID) FROM Activity WHERE $innerClause ) ";

    //error_log( __FUNCTION__ . ">" . $query );

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        error_log($query);
        error_log(__FUNCTION__ . " failed! $user, $type, $data");
        return false;
    }

    return mysqli_fetch_assoc($dbResult);
}

//    01:01 23/02/2013
function updateActivity($activityID)
{
    //    Update the last update value of given activity
    $query = "UPDATE Activity
              SET Activity.lastupdate = NOW()
              WHERE Activity.ID = $activityID ";

    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        error_log($query);
        error_log(__FUNCTION__ . " failed! $activityID");
    }
}

//    08:17 22/09/2014
function RecentlyPostedCompletionActivity($user, $gameID, $isHardcore)
{
    settype($isHardcore, 'integer');

    $query = "SELECT act.ID
              FROM Activity AS act
              WHERE act.user='$user' AND act.data='$gameID' AND act.data2='$isHardcore' AND act.lastupdate >= DATE_SUB( NOW(), INTERVAL 1 HOUR )
              LIMIT 1";

    $dbResult = s_mysql_query($query);

    SQL_ASSERT($dbResult);
    return (mysqli_num_rows($dbResult) > 0);
}

//    01:04 23/02/2013
function postActivity($userIn, $activity, $customMsg, $isalt = null)
{
    $user = correctUserCase($userIn);

    userActivityPing($user);

    //    Remove single quotes!
    $customMsg = str_replace("'", "''", $customMsg);
    $query = "INSERT INTO Activity (lastupdate, activitytype, user, data, data2) VALUES ";

    switch ($activity) {
        case ActivityType::EarnedAchivement:
            //    earned an achievement
            $achID = $customMsg;

            getAchievementMetadata($achID, $achData);

            $gameName = $achData['GameTitle'];
            $gameID = $achData['GameID'];
            $achName = $achData['AchievementTitle'];

            $gameLink = "<a href='/Game/$gameID'>$gameName</a>";
            $achLink = "<a href='/Achievement/$achID'>$achName</a>";

            $gameLink = str_replace("'", "''", $gameLink);
            $achLink = str_replace("'", "''", $achLink);

            $query .= "(NOW(), $activity, '$user', '$achID', $isalt )";
            break;

        case ActivityType::Login:
            //    login
            $lastLoginActivity = getMostRecentActivity($user, $activity, null);
            if (isset($lastLoginActivity)) {
                $nowTimestamp = time();
                $lastLoginTimestamp = strtotime($lastLoginActivity['timestamp']);
                $diff = $nowTimestamp - $lastLoginTimestamp;

                if ($diff < 60 * 60 * 6) //    6 hours
                {
                    //error_log( __FUNCTION__ . " new login activity from $user, duplicate of recent login " . ($diff/60) . " mins ago, so ignoring!" );
                    return;
                }
            }

            $query .= "(NOW(), $activity, '$user', NULL, NULL)";
            break;

        case ActivityType::StartedPlaying:
            //    start playing game
            $gameID = $customMsg;
            getGameTitleFromID($gameID, $gameTitle, $consoleIDOut, $consoleName, $forumTopicID, $gameData);

            //    Check for recent duplicate:
            $lastPlayedActivityData = getMostRecentActivity($user, $activity, $gameID);
            if (isset($lastPlayedActivityData)) {
                $nowTimestamp = time();
                $lastPlayedTimestamp = strtotime($lastPlayedActivityData['timestamp']);
                $diff = $nowTimestamp - $lastPlayedTimestamp;

                if ($diff < 60 * 60 * 12) //    12 hours
                {
                    //error_log( __FUNCTION__ . " new playing $gameTitle activity from $user, duplicate of recent activity " . ($diff/60) . " mins ago. Updating db, but not posting!" );

                    updateActivity($lastPlayedActivityData['ID']);
                    return;
                } else {
                    //error_log( __FUNCTION__ . " recognises that $user has played $gameTitle recently, but longer than 12 hours ago (" . ($diff/60) . " mins) so still posting activity! $nowTimestamp - $lastPlayedTimestamp = $diff" );
                }
            }

            $gameTitle = str_replace("'", "''", $gameTitle);

            $query .= "(NOW(), $activity, '$user', '$gameID', NULL)";
            //error_log( $query );
            break;

        case ActivityType::UploadAchievement:
        case ActivityType::EditAchievement:
        case ActivityType::OpenedTicket:
        case ActivityType::ClosedTicket:
            $achID = $customMsg;
            $achievementName = getAchievementTitle($achID, $gameTitle, $gameID);

            if ($activity == ActivityType::OpenedTicket || $activity == ActivityType::ClosedTicket) {
                $achievementLink = "<a href='/ticketmanager.php?a=$achID&t=1'>$achievementName</a>";
            } else {
                $achievementLink = "<a href='/Achievement/$achID'>$achievementName</a>";
            }
            $achievementLink = str_replace("'", "''", $achievementLink);

            $query .= "(NOW(), $activity, '$user', '$achID', NULL)";
            break;

        case ActivityType::CompleteGame:
            //    Completed a game!
            $gameID = $customMsg;
            getGameTitleFromID($gameID, $gameTitle, $consoleIDOut, $consoleName, $forumTopicID, $gameData);

            $gameLink = "<a href='/Game/$gameID'>$gameTitle</a>";
            $gameLink = str_replace("'", "''", $gameLink);

            AddSiteAward($user, 1, $gameID, $isalt);

            $query .= "(NOW(), $activity, '$user', '$gameID', $isalt)";
            break;

        case ActivityType::NewLeaderboardEntry:
        case ActivityType::ImprovedLeaderboardEntry:
            $lbID = $customMsg['LBID'];
            $lbTitle = $customMsg['LBTitle'];
            $score = $customMsg['Score'];
            $gameID = $customMsg['GameID'];
            $scoreFormatted = $customMsg['ScoreFormatted'];
            getGameTitleFromID($gameID, $gameTitle, $consoleIDOut, $consoleName, $forumTopicID, $gameData);

            $gameLink = "<a href='/Game/$gameID'>$gameTitle</a>";
            $gameLink = str_replace("'", "''", $gameLink);
            $lbLinkScore = "<a href='/leaderboardinfo.php?i=$lbID'>$scoreFormatted</a>";
            $lbLinkScore = str_replace("'", "''", $lbLinkScore);
            $lbLinkTitle = "<a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a>";
            $lbLinkTitle = str_replace("'", "''", $lbLinkTitle);

            $query .= "(NOW(), $activity, '$user', '$lbID', '$score')";
            break;

        default: //ft
        case ActivityType::Unknown:
            error_log(__FUNCTION__ . " received unknown activity: $activity");
            $query .= "(NOW(), $activity, '$user', '$customMsg', '$customMsg')";
            break;
    }

    //error_log( __FUNCTION__ . " - " . $query );
    //log_sql( $query );

    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult == false) {
        log_sql_fail();
    } else {
        //    Update UA
        global $db;
        $newActID = mysqli_insert_id($db);
        $query = "UPDATE UserAccounts AS ua SET ua.LastActivityID = $newActID, ua.LastLogin = NOW() WHERE ua.User = '$user'";
        $dbResult = s_mysql_query($query);
        if ($dbResult == false) {
            log_sql_fail();
        }
    }

    return ($dbResult !== false);
}

function userActivityPing($user)
{
    if (!isset($user) || strlen($user) < 2) {
        //error_log( __FUNCTION__ . " fucked up somehow for $user" );
        //log_email( __FUNCTION__ . " fucked up" );
        return false;
    }

    $query = "UPDATE UserAccounts AS ua
              SET ua.LastLogin = NOW()
              WHERE ua.User = '$user' ";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        error_log(__FUNCTION__ . " fucked up somehow for $user");
        //log_sql_fail();
        //log_email( __FUNCTION__ . " fucked up somehow for $user" );
        return false;
    }

    return true;
}

//    23:13 12/01/2014
function UpdateUserRichPresence($user, $gameID, $presenceMsg)
{
    if (!isset($user) || strlen($user) < 2) {
        //log_email( __FUNCTION__ . " fucked up ($user, $gameID, $presenceMsg)" );
        error_log(__FUNCTION__ . " fucked up ($user, $gameID, $presenceMsg)");
        return false;
    }

    global $db;

    settype($gameID, 'integer');
    $presenceMsg = mysqli_real_escape_string($db, $presenceMsg);
    $user = mysqli_real_escape_string($db, $user);

    $query = "UPDATE UserAccounts AS ua
              SET ua.RichPresenceMsg = '$presenceMsg', ua.LastGameID = '$gameID', ua.RichPresenceMsgDate = NOW()
              WHERE ua.User = '$user' ";

    $dbResult = mysqli_query($db, $query); //    Allow direct: we have sanitized all variables
    if ($dbResult == false) {
        log_email(__FUNCTION__ . " fucked up somehow for $user");
        return false;
    }

    return true;
}

//    13:33 27/04/2013
function informAllSubscribersAboutActivity($activityAuthor, $threadAuthor, $articleID, $articleType)
{
    $requiredSubscription = 0;
    $altURLTarget = null;

    //    $articleType
    //    1 = Game
    //    2 = Achievement
    //    3 = User
    //    4 = News (unused)
    //    5 = feed Activity
    //    6 = LB
    //    7 = Ticket

    if ($articleType == 1)   //    Game
    {
        //    None
        $requiredSubscription = (1 << 1);  //    Matches Achievement
        //    Fetch all users that have also commented on this game:
        $query = "SELECT DISTINCT ua.User, ua.EmailAddress, ua.websitePrefs
                  FROM Comment AS c
                  INNER JOIN UserAccounts AS ua ON ua.ID = c.UserID
                  WHERE c.ArticleID=$articleID AND c.ArticleType=1 ";
    } elseif ($articleType == 2)   //    Achievement
    {
        $requiredSubscription = (1 << 1);

        //    Walks a thread and emails all users that are subscribed to updates
        //    Fetch all users that are involved in this thread:
        $query = "SELECT DISTINCT ua.User, ua.EmailAddress, ua.websitePrefs
                  FROM Comment AS c
                  INNER JOIN UserAccounts AS ua ON ua.ID = c.UserID
                  WHERE ( c.ArticleID=$articleID AND c.ArticleType=$articleType ) OR ua.User = '$threadAuthor'";
    } elseif ($articleType == 3)   //    User
    {
        $requiredSubscription = (1 << 2);
        $altURLTarget = $threadAuthor; //    Override: this means we should provide a link to the target user's wall instead!
        //    Walks a thread and emails all users that are subscribed to updates
        //    Fetch all users that are involved in this thread:
        $query = "SELECT DISTINCT ua.User, ua.EmailAddress, ua.websitePrefs
                  FROM Comment AS c
                  INNER JOIN UserAccounts AS ua ON ua.ID = c.UserID
                  WHERE ( c.ArticleID=$articleID AND c.ArticleType=$articleType ) OR ua.User = '$threadAuthor'";
    } elseif ($articleType == 4)   //    News
    {
        //    None (me?)
        error_log(__FUNCTION__ . " cannot deal with articleType $articleType (News)");
        return;
    } elseif ($articleType == 5)     //    Activity (feed)
    {
        $requiredSubscription = (1 << 0);

        //    Walks a thread and emails all users that are subscribed to updates
        //    Fetch all users that are involved in this thread:
        $query = "SELECT DISTINCT ua.User, ua.EmailAddress, ua.websitePrefs
                  FROM Comment AS c
                  INNER JOIN UserAccounts AS ua ON ua.ID = c.UserID
                  WHERE ( c.ArticleID=$articleID AND c.ArticleType=$articleType ) OR ua.User = '$threadAuthor'";
    } elseif ($articleType == 7)     //    Ticket
    {
        $requiredSubscription = (1 << 1);  //    Matches Achievement (?)
        //    Walks a thread and emails all users that are subscribed to updates
        //    Fetch all users that are involved in this thread:
        $query = "SELECT DISTINCT ua.User, ua.EmailAddress, ua.websitePrefs
                  FROM Comment AS c
                  INNER JOIN UserAccounts AS ua ON ua.ID = c.UserID
                  WHERE ( c.ArticleID=$articleID AND c.ArticleType=$articleType ) OR ua.User = '$threadAuthor'";
    } else {
        log_email(__FUNCTION__ . " cannot deal with articleType $articleType (Unknown)");
        return;
    }

    //error_log( $query );
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        //    Foreach person who has commented on this thread, if they want it, send them an email.
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if ($data !== false) {
                if ((($data['websitePrefs'] & (1 << 0)) !== 0) || $articleType == 7) //    Always receive ticket emails :P
                {
                    $justInvolvedInThread = null;
                    if (($data['User'] != $threadAuthor) && ($data['User'] != $activityAuthor)) {
                        $justInvolvedInThread = true;
                    }

                    sendActivityEmail($data['User'], $data['EmailAddress'], $articleID, $activityAuthor, $articleType,
                        $justInvolvedInThread, $altURLTarget);
                } else {
                    error_log("Not sending email to " . $data['User'] . ", prefs are " . $data['websitePrefs']);
                }
            } else {
                error_log($query);
                error_log(__FUNCTION__ . "error: $activityAuthor, $articleID, $commentPayload");
            }
        }

        return true;
    } else {
        error_log($query);
        error_log(__FUNCTION__ . "error2: $activityAuthor, $articleID, $commentPayload");
        return false;
    }
}

//  08/19/2014 13:13:04
function RemoveComment($articleID, $commentID)
{
    settype($articleID, 'integer');
    settype($commentID, 'integer');

    $query = "DELETE FROM Comment
              WHERE ArticleID = $articleID AND ID = $commentID";

    log_sql($query);

    global $db;
    $dbResult = mysqli_query($db, $query);

    if ($dbResult == false) {
        log_sql_fail();
        return false;
    } else {
        return (mysqli_affected_rows($db) > 0);
    }
}

//    20:05 06/04/2013
function addArticleComment($user, $articleType, $articleID, $commentPayload)
{
    //    Note: $user is the person who just made a comment.

    $userID = getUserIDFromUser($user);
    if ($userID == 0) {
        error_log(__FUNCTION__ . "error3: $user, $articleType, $articleID, $commentPayload");
        return false;
    }

    //error_log( __FUNCTION__ . $commentPayload );
    //    Replace all single quotes with double quotes (to work with MYSQL DB)
    //$commentPayload = str_replace( "'", "''", $commentPayload );
    global $db;
    $commentPayload = mysqli_real_escape_string($db, $commentPayload);

    $query = "INSERT INTO Comment VALUES( NULL, $articleType, $articleID, $userID, '$commentPayload', NOW(), NULL )";
    log_sql($query);

    $dbResult = mysqli_query($db, $query);

    if ($dbResult == false) {
        log_sql_fail();
        return false;
    }

    //    Inform Subscribers of this comment:

    if ($articleType == 5)   //    Activity
    {
        error_log(__FUNCTION__ . " Comment on Activity... notifying author and thread");

        //    Get activity's original author:
        $activityData = getActivityMetadata($articleID);
        informAllSubscribersAboutActivity($user, $activityData['User'], $articleID, $articleType);
    } elseif ($articleType == 2) // Achievement
    {
        error_log(__FUNCTION__ . " Comment on Achievement... notifying author and thread");

        //    Get achievement's original author:
        $achievementData = getAchievementMetadataJSON($articleID);

        // if it's a message from the "Server" logging a change made by the Author,
        // assume the Author is posting the activity (avoid spamming their mail box).
        if ($user == "Server" && !strncmp($achievementData['Author'] . ' ', $commentPayload,
                strlen($achievementData['Author']) + 1)) {
            informAllSubscribersAboutActivity($achievementData['Author'], $achievementData['Author'], $articleID,
                $articleType);
        } else {
            informAllSubscribersAboutActivity($user, $achievementData['Author'], $articleID, $articleType);
        }
    } elseif ($articleType == 3) //    User
    {
        error_log(__FUNCTION__ . " Comment on User... notifying author and thread");

        //    $articleID          = ID of the User who's page we've written on.
        //    $userData['User']     = the name of the user who's wall has just been written on.
        //    $user                 = the user who has just written on this wall.
        //    Get
        $userData = getUserMetadataFromID($articleID);
        informAllSubscribersAboutActivity($user, $userData['User'], $articleID, $articleType);
    } elseif ($articleType == 4) //    News
    {
        //    Not interested in any further activity at this stage.
        error_log(__FUNCTION__ . " Comment on News article... notify nobody? ");
    } elseif ($articleType == 1) //    Game
    {
        //log_sql_fail();
        getGameTitleFromID($articleID, $gameName, $consoleID, $consoleName, $forumTopicID, $gameData);
        //    $user commented on $gameName, which is game ID $articleID (type 1).
        informAllSubscribersAboutActivity($user, $gameName, $articleID, $articleType);
        //error_log( __FUNCTION__ . " Comment on Game... notify nobody? " );
    } elseif ($articleType == 7) //    Ticket
    {
        //    $user commented on ticket $articleID
        $ticketData = getTicket($articleID);
        informAllSubscribersAboutActivity($user, $ticketData['ReportedBy'], $articleID, $articleType);
    }

    return true;
}

//     00:08 19/03/2013
function getActivityMetadata($activityID)
{
    $query = "SELECT * FROM Activity
              WHERE ID='$activityID'";

    $dbResult = s_mysql_query($query);
    return mysqli_fetch_assoc($dbResult);
}

//    00:08 19/03/2013
function getFeed($user, $maxMessages, $offset, &$dataOut, $latestFeedID = 0, $type = 'global')
{
    settype($maxMessages, "integer");
    settype($offset, "integer");

    if ($type == 'activity')      //    Find just this activity, ONLY!
    {
        $subquery = "act.ID = $latestFeedID ";
    } elseif ($type == 'friends')     //    User has been provided: find my friends!
    {
        $subquery = "act.ID > $latestFeedID AND ( act.user = '$user' OR act.user IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$user' ) )";
    } elseif ($type == 'individual')    //    User and 'individual', just this user's feed!
    {
        $subquery = "act.ID > $latestFeedID AND ( act.user = '$user' )";
    } else //if( $type == 'global' )                    //    Otherwise, global feed
    {
        $subquery = "act.ID > $latestFeedID ";
    }

    $query = "
    SELECT InnerTable.ID, UNIX_TIMESTAMP(timestamp) AS timestamp, activitytype, InnerTable.User, uaLocal.RAPoints, uaLocal.Motto, data, data2, gd.Title AS GameTitle, gd.ID AS GameID, gd.ImageIcon AS GameIcon, cons.Name AS ConsoleName,
        ach.Title AS AchTitle, ach.Description AS AchDesc, ach.Points AS AchPoints, ach.BadgeName AS AchBadge,
        lb.Title AS LBTitle, lb.Description AS LBDesc, lb.Format AS LBFormat,
        ua.User AS CommentUser, ua.Motto AS CommentMotto, ua.RAPoints AS CommentPoints, c.Payload AS Comment, UNIX_TIMESTAMP(c.Submitted) AS CommentPostedAt, c.ID AS CommentID
    FROM
    (
        SELECT act.ID, act.timestamp, act.activitytype, act.User, act.data, act.data2
        FROM Activity AS act
        LEFT JOIN UserAccounts AS ua ON ua.User = act.User
        WHERE ( !ua.Untracked || ua.User=\"$user\" ) AND $subquery
        ORDER BY act.ID DESC
        LIMIT $offset, $maxMessages
    ) AS InnerTable

    LEFT JOIN LeaderboardDef AS lb ON
    (
        activitytype IN (7, 8)
        AND InnerTable.data = lb.ID
    )
    LEFT JOIN Achievements AS ach ON
    (
        activitytype IN (1, 4, 5, 9, 10)
        AND ach.ID = InnerTable.data
    )
    LEFT JOIN GameData AS gd ON
    (
        activitytype IN (1, 4, 5, 9, 10)
        AND gd.ID = ach.GameID
    ) OR (
        activitytype IN (3, 6)
        AND gd.ID = InnerTable.data
    ) OR (
        activitytype IN (7, 8)
        AND gd.ID = lb.GameID
    )

    LEFT JOIN Console AS cons ON cons.ID = gd.ConsoleID
    LEFT JOIN Comment AS c ON c.ArticleID = InnerTable.ID
    LEFT JOIN UserAccounts AS ua ON ua.ID = c.UserID
    LEFT JOIN UserAccounts AS uaLocal ON uaLocal.User = InnerTable.User

    ORDER BY InnerTable.ID ASC, c.ID DESC
    ";

    //error_log( $query );

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = array();

        $i = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$i] = $db_entry;
            $i++;
        }

        return $i;
    } else {
        error_log($query);
        error_log(__FUNCTION__ . " Failed! user=$user");
    }

    return 0;
}

//01:50 22/03/2013
function getRecentlyPlayedGames($user, $offset, $count, &$dataOut)
{
    // $query = "SELECT g.ID AS GameID, g.ConsoleID, c.Name AS ConsoleName, g.Title, MAX(act.lastupdate) AS LastPlayed, g.ImageIcon
    // FROM Activity AS act
    // LEFT JOIN GameData AS g ON g.ID = act.data
    // LEFT JOIN Console AS c ON c.ID = g.ConsoleID
    // WHERE act.user='$user' AND act.activitytype=3
    // GROUP BY g.ID
    // ORDER BY MAX(act.lastupdate) DESC
    // LIMIT $offset, $count ";
    //    19:30 02/02/2014 rewritten without MAX() and using an inner query. ~300% faster but I don't know why... :(
    //    02:51 03/02/2014 re-rewritten with MAX()
    //    01:38 15/02/2014 re-readded 'AND act.activitytype = 3' to inner query. act.data is not necessarily a game, therefore we need this '3' part.
    //    22:56 18/02/2014 re-re-readded 'MAX() to inner.
    //    08:05 01/10/2014 removed outer activitytype=3, added rating
    //{$ObjectType::Game}

    $query = "
SELECT Inner1.data AS GameID, gd.ConsoleID, c.Name AS ConsoleName, gd.Title, gd.ImageIcon, Inner1.lastupdate AS LastPlayed, r.RatingValue AS MyVote
FROM (
 SELECT act.ID, MAX( act.lastupdate ) AS lastupdate, act.data, act.activitytype
 FROM Activity AS act
 WHERE act.user='$user' AND act.activitytype = 3
 GROUP BY act.data
 ORDER BY MAX( act.lastupdate ) DESC
 ) AS Inner1
LEFT JOIN GameData AS gd ON gd.ID = Inner1.data
LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
LEFT JOIN Rating AS r ON r.RatingObjectType = " . ObjectType::Game . " AND r.RatingID=Inner1.data AND r.User='$user'
LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);

    $numFound = 0;

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numFound] = $data;
            //$dataOut[$data['GameID']] = $data;
            $numFound++;
        }
    } else {
        error_log($query);
        error_log(__FUNCTION__ . " had issues... $user, $offset, $count");
    }

    return $numFound;
}

function getArticleComments($articleTypeID, $articleID, $offset, $count, &$dataOut)
{
    //    $articleTypeID
    //    1 = Game
    //    2 = Achievement
    //    3 = User
    //    4 = News (unused)
    //    5 = feed Activity
    //    6 = LB
    //    7 = Ticket

    $dataOut = array();

    $numArticleComments = 0;

    $query = "
        SELECT ua.User, ua.RAPoints, ua.Motto, LatestComments.ID, LatestComments.UserID, LatestComments.Payload AS CommentPayload, UNIX_TIMESTAMP(LatestComments.Submitted) AS Submitted, LatestComments.Edited
        FROM
        (
            SELECT c.ID, c.UserID, c.Payload, c.Submitted, c.Edited
            FROM Comment AS c
            WHERE c.ArticleType=$articleTypeID AND c.ArticleID=$articleID
            ORDER BY c.Submitted DESC
            LIMIT $offset, $count
        ) AS LatestComments
        LEFT JOIN UserAccounts AS ua ON ua.ID = LatestComments.UserID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numArticleComments] = $db_entry;
            $numArticleComments++;
        }
    } else {
        error_log(__FUNCTION__ . " failed, $articleTypeID, $articleID, $offset, $count ");
        error_log($query);
    }

    //    Fetch the last elements by submitted, but return them here in top-down order.
    $dataOut = array_reverse($dataOut);

    return $numArticleComments;
}

function getCurrentlyOnlinePlayers()
{
    $recentMinutes = 10;

    $playersFound = array();

    //    Select all users active in the last 10 minutes:
    $query = "SELECT ua.User, ua.RAPoints, ua.Motto, act.timestamp AS LastActivityAt, ua.RichPresenceMsg AS LastActivity
              FROM UserAccounts AS ua
              LEFT JOIN Activity AS act ON act.ID = ua.LastActivityID
              WHERE ua.LastLogin > TIMESTAMPADD( MINUTE, -$recentMinutes, NOW() )
              ORDER BY ua.ID ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['RAPoints'], 'integer');
            settype($db_entry['GameID'], 'integer');
            $playersFound[] = $db_entry;
        }
    } else {
        error_log($query);
        error_log(__FUNCTION__ . " failed3: user:$user gameID:$gameID");
    }

    return $playersFound;
}

function getLatestRichPresenceUpdates()
{
    $playersFound = array();

    $recentMinutes = 10;

    $query = "SELECT ua.User, ua.RAPoints, ua.Motto, ua.RichPresenceMsg, gd.ID AS GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleName
              FROM UserAccounts AS ua
              LEFT JOIN GameData AS gd ON gd.ID = ua.LastGameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ua.RichPresenceMsgDate > TIMESTAMPADD( MINUTE, -$recentMinutes, NOW() ) AND ua.LastGameID !=0";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['GameID'], 'integer');
            settype($db_entry['RAPoints'], 'integer');
            $playersFound[] = $db_entry;
        }
    } else {
        error_log($query);
        error_log(__FUNCTION__ . " failed3: user:$user gameID:$gameID");
    }

    return $playersFound;
}

function getLatestNewAchievements($numToFetch, &$dataOut)
{
    $numFound = 0;

    $query = "SELECT ach.ID, ach.GameID, ach.Title, ach.Description, ach.Points, gd.Title AS GameTitle, gd.ImageIcon as GameIcon, ach.DateCreated, UNIX_TIMESTAMP(ach.DateCreated) AS timestamp, ach.BadgeName, c.Name AS ConsoleName
              FROM Achievements AS ach
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              ORDER BY DateCreated DESC
              LIMIT 0, $numToFetch ";

    //echo $query;

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numFound] = $db_entry;
            $numFound++;
        }
    } else {
        //log_email( __FUNCTION__ . " cannot deal with this function..." );
        error_log($query);
        error_log(__FUNCTION__ . " failed: $numToFetch");
    }

    return $numFound;
}

function GetMostPopularTitles($daysRange = 7, $offset = 0, $count = 10)
{
    $data = array();

    $query = "SELECT COUNT(*) as PlayedCount, gd.ID, gd.Title, gd.ImageIcon, c.Name as ConsoleName
FROM Activity AS act
LEFT JOIN GameData AS gd ON gd.ID = act.data
LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
WHERE ( act.timestamp BETWEEN TIMESTAMPADD( DAY, -$daysRange, NOW() ) AND NOW() ) AND ( act.activitytype = 3 ) AND ( act.data > 0 )
GROUP BY act.data
ORDER BY PlayedCount DESC
LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);

    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $data[] = $nextData;
    }

    return $data;
}
