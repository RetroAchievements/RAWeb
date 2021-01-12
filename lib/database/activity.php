<?php

use RA\ActivityType;
use RA\ArticleType;
use RA\ObjectType;
use RA\Permissions;

function getMostRecentActivity($user, $type, $data)
{
    sanitize_sql_inputs($user, $type, $data);

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
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed! $user, $type, $data");
        return false;
    }

    return mysqli_fetch_assoc($dbResult);
}

function updateActivity($activityID)
{
    sanitize_sql_inputs($activityID);

    //    Update the last update value of given activity
    $query = "UPDATE Activity
              SET Activity.lastupdate = NOW()
              WHERE Activity.ID = $activityID ";

    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed! $activityID");
    }
}

function RecentlyPostedCompletionActivity($user, $gameID, $isHardcore)
{
    sanitize_sql_inputs($user, $gameID, $isHardcore);
    settype($isHardcore, 'integer');

    $query = "SELECT act.ID
              FROM Activity AS act
              WHERE act.user='$user' AND act.data='$gameID' AND act.data2='$isHardcore' AND act.lastupdate >= DATE_SUB( NOW(), INTERVAL 1 HOUR )
              LIMIT 1";

    $dbResult = s_mysql_query($query);

    SQL_ASSERT($dbResult);
    return mysqli_num_rows($dbResult) > 0;
}

function postActivity($userIn, $activity, $customMsg, $isalt = null)
{
    global $db;

    $user = validateUsername($userIn);
    if (!$user) {
        return false;
    }

    userActivityPing($user);

    // Remove single quotes!
    $customMsg = str_replace("'", "''", $customMsg);
    $query = "INSERT INTO Activity (lastupdate, activitytype, user, data, data2) VALUES ";

    switch ($activity) {
        case ActivityType::EarnedAchivement:
            $achID = $customMsg;

            $achData = [];
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
            $lastLoginActivity = getMostRecentActivity($user, $activity, null);
            if ($lastLoginActivity) {
                $nowTimestamp = time();
                $lastLoginTimestamp = strtotime($lastLoginActivity['timestamp']);
                $diff = $nowTimestamp - $lastLoginTimestamp;

                /**
                 * record login activity only every 6 hours
                 */
                if ($diff < 60 * 60 * 6) {
                    /**
                     * new login activity from $user, duplicate of recent login " . ($diff/60) . " mins ago,
                     * ignoring!
                     */
                    return true;
                }
            }
            $query .= "(NOW(), $activity, '$user', NULL, NULL)";
            break;

        case ActivityType::StartedPlaying:
            $gameID = $customMsg;
            getGameTitleFromID($gameID, $gameTitle, $consoleIDOut, $consoleName, $forumTopicID, $gameData);

            /**
             * Check for recent duplicate:
             */
            $lastPlayedActivityData = getMostRecentActivity($user, $activity, $gameID);
            if (isset($lastPlayedActivityData)) {
                $nowTimestamp = time();
                $lastPlayedTimestamp = strtotime($lastPlayedActivityData['timestamp']);
                $diff = $nowTimestamp - $lastPlayedTimestamp;

                /**
                 * record game session activity only every 12 hours
                 */
                if ($diff < 60 * 60 * 12) {
                    /**
                     * new playing $gameTitle activity from $user, duplicate of recent activity " . ($diff/60) . " mins ago
                     * Updating db, but not posting!
                     */
                    updateActivity($lastPlayedActivityData['ID']);
                    return true;
                } else {
                    /**
                     * recognises that $user has played $gameTitle recently, but longer than 12 hours ago (" . ($diff/60) . " mins) so still posting activity!
                     * $nowTimestamp - $lastPlayedTimestamp = $diff
                     */
                }
            }

            $gameTitle = str_replace("'", "''", $gameTitle);

            $query .= "(NOW(), $activity, '$user', '$gameID', NULL)";
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

        default:
        case ActivityType::Unknown:
            error_log(__FUNCTION__ . " received unknown activity: $activity");
            $query .= "(NOW(), $activity, '$user', '$customMsg', '$customMsg')";
            break;
    }

    $dbResult = mysqli_query($db, $query);
    if ($dbResult == false) {
        log_sql_fail();
    } else {
        /**
         * Update UserAccount
         */
        $newActID = mysqli_insert_id($db);
        $query = "UPDATE UserAccounts AS ua SET ua.LastActivityID = $newActID, ua.LastLogin = NOW() WHERE ua.User = '$user'";
        $dbResult = s_mysql_query($query);
        if ($dbResult == false) {
            log_sql_fail();
        }
    }

    return $dbResult !== false;
}

function userActivityPing($user)
{
    if (!isset($user) || mb_strlen($user) < 2) {
        return false;
    }
    sanitize_sql_inputs($user);

    $query = "UPDATE UserAccounts AS ua
              SET ua.LastLogin = NOW()
              WHERE ua.User = '$user' ";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_sql_fail();
        return false;
    }

    return true;
}

function UpdateUserRichPresence($user, $gameID, $presenceMsg)
{
    if (!isset($user) || mb_strlen($user) < 2) {
        return false;
    }
    sanitize_sql_inputs($user, $gameID, $presenceMsg);
    settype($gameID, 'integer');

    $query = "UPDATE UserAccounts AS ua
              SET ua.RichPresenceMsg = '$presenceMsg', ua.LastGameID = '$gameID', ua.RichPresenceMsgDate = NOW()
              WHERE ua.User = '$user' ";

    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult == false) {
        log_sql_fail();
        return false;
    }

    return true;
}

function getActivityMetadata($activityID)
{
    sanitize_sql_inputs($activityID);

    $query = "SELECT * FROM Activity
              WHERE ID='$activityID'";

    $dbResult = s_mysql_query($query);
    return mysqli_fetch_assoc($dbResult);
}

function RemoveComment($articleID, $commentID, $userID, $permissions)
{
    settype($articleID, 'integer');
    settype($commentID, 'integer');
    settype($userID, 'integer');
    settype($permissions, 'integer');

    $query = "DELETE FROM Comment
              WHERE ArticleID = $articleID AND ID = $commentID";

    // if not UserWall's owner nor admin, check if it's the author
    if ($articleID != $userID && $permissions < \RA\Permissions::Admin) {
        $query .= " AND UserID = $userID";
    }

    // log_sql($query);

    global $db;
    $dbResult = mysqli_query($db, $query);

    if ($dbResult == false) {
        log_sql_fail();
        return false;
    } else {
        s_mysql_query("INSERT INTO DeletedModels SET ModelType='Comment', ModelID=$commentID");
        return mysqli_affected_rows($db) > 0;
    }
}

function addArticleComment($user, $articleType, $articleID, $commentPayload, $onBehalfOfUser = null)
{
    if (!\RA\ArticleType::isValid($articleType)) {
        return false;
    }

    sanitize_sql_inputs($activityID, $commentPayload);

    //    Note: $user is the person who just made a comment.

    $userID = getUserIDFromUser($user);
    if ($userID == 0) {
        // error_log(__FUNCTION__ . "error3: $user, $articleType, $articleID, $commentPayload");
        return false;
    }

    //error_log( __FUNCTION__ . $commentPayload );
    //    Replace all single quotes with double quotes (to work with MYSQL DB)
    //$commentPayload = str_replace( "'", "''", $commentPayload );

    if (is_array($articleID)) {
        $arrayCount = count($articleID);
        $count = 0;
        $query = "INSERT INTO Comment VALUES";
        foreach ($articleID as $id) {
            $query .= "( NULL, $articleType, $id, $userID, '$commentPayload', NOW(), NULL )";
            if (++$count !== $arrayCount) {
                $query .= ",";
            }
        }
    } else {
        $query = "INSERT INTO Comment VALUES( NULL, $articleType, $articleID, $userID, '$commentPayload', NOW(), NULL )";
    }
    // log_sql($query);

    global $db;
    $dbResult = mysqli_query($db, $query);

    if ($dbResult == false) {
        log_sql_fail();
        return false;
    }

    //    Inform Subscribers of this comment:
    if (is_array($articleID)) {
        foreach ($articleID as $id) {
            informAllSubscribersAboutActivity($articleType, $id, $user, $onBehalfOfUser);
        }
    } else {
        informAllSubscribersAboutActivity($articleType, $articleID, $user, $onBehalfOfUser);
    }

    return true;
}

function informAllSubscribersAboutActivity($articleType, $articleID, $activityAuthor, $onBehalfOfUser)
{
    $subscribers = [];
    $subjectAuthor = null;
    $altURLTarget = null;

    switch ($articleType) {
        case ArticleType::Game:
            $subscribers = getSubscribersOfGameWall($articleID);
            break;

        case ArticleType::Achievement:
            $achievementData = getAchievementMetadataJSON($articleID);
            $subscribers = getSubscribersOfAchievement($articleID, $achievementData['GameID'], $achievementData['Author']);
            $subjectAuthor = $achievementData['Author'];
            break;

        case ArticleType::User:  // User wall
            $wallUserData = getUserMetadataFromID($articleID);
            $subscribers = getSubscribersOfUserWall($articleID, $wallUserData['User']);
            $subjectAuthor = $wallUserData['User'];
            $altURLTarget = $wallUserData['User'];
            break;

        case ArticleType::News:  // News
            break;

        case ArticleType::Activity:  // Activity (feed)
            $activityData = getActivityMetadata($articleID);
            $subscribers = getSubscribersOfFeedActivity($articleID, $activityData['User']);
            $subjectAuthor = $activityData['User'];
            break;

        case ArticleType::Leaderboard:  // Leaderboard
            break;

        case ArticleType::AchievementTicket:  // Ticket
            $ticketData = getTicket($articleID);
            $subscribers = getSubscribersOfTicket($articleID, $ticketData['ReportedBy'], $ticketData['GameID']);
            $subjectAuthor = $ticketData['ReportedBy'];
            break;

        default:
            break;
    }

    // some comments are generated by the user "Server" on behalf of other users whom we don't want to notify
    if ($onBehalfOfUser !== null) {
        $activityAuthor = $onBehalfOfUser;
    }

    foreach ($subscribers as $subscriber) {
        $isThirdParty = ($subscriber['User'] != $activityAuthor && ($subjectAuthor === null || $subscriber['User'] != $subjectAuthor));

        sendActivityEmail($subscriber['User'], $subscriber['EmailAddress'], $articleID, $activityAuthor, $articleType, $isThirdParty, $altURLTarget);
    }
}

function getSubscribersOfGameWall($gameID)
{
    return getSubscribersOfArticle(1, $gameID, (1 << 1));
}

function getSubscribersOfAchievement($achievementID, $gameID, $achievementAuthor)
{
    // users directly subscribed to the achievement
    $achievementSubs = getSubscribersOfArticle(2, $achievementID, (1 << 1), $achievementAuthor);

    // devs subscribed to the achievement through the game
    $gameAchievementsSubs = getSubscribersOf(\RA\SubscriptionSubjectType::GameAchievements, $gameID, (1 << 0) /*(1 << 1)*/);

    return mergeSubscribers($achievementSubs, $gameAchievementsSubs);
}

function getSubscribersOfUserWall($userID, $userName)
{
    return getSubscribersOfArticle(3, $userID, (1 << 2), $userName);
}

function getSubscribersOfFeedActivity($activityID, $author)
{
    return getSubscribersOfArticle(5, $activityID, (1 << 0), $author, true);
}

function getSubscribersOfTicket($ticketID, $ticketAuthor, $gameID)
{
    // users directly subscribed to the ticket
    $ticketSubs = getSubscribersOfArticle(7, $ticketID, (1 << 1), $ticketAuthor, true);

    // devs subscribed to the ticket through the game
    $gameTicketsSubs = getSubscribersOf(\RA\SubscriptionSubjectType::GameTickets, $gameID, (1 << 0) /*(1 << 1)*/);

    return mergeSubscribers($ticketSubs, $gameTicketsSubs);
}

function getSubscribersOfArticle(
    $articleType,
    $articleID,
    $reqWebsitePrefs,
    $subjectAuthor = null,
    $noExplicitSubscriptions = false
) {
    $websitePrefsFilter = ($noExplicitSubscriptions !== true
        ? "" : "AND (_ua.websitePrefs & $reqWebsitePrefs) != 0");

    $authorQry = ($subjectAuthor === null ? "" : "
        UNION
        SELECT _ua.*
        FROM UserAccounts as _ua
        WHERE _ua.User = '$subjectAuthor'
              $websitePrefsFilter
    ");

    $qry = "
        SELECT DISTINCT _ua.*
        FROM Comment AS _c
        INNER JOIN UserAccounts as _ua ON _ua.ID = _c.UserID
        WHERE _c.ArticleType = $articleType
              AND _c.ArticleID = $articleID
              $websitePrefsFilter
        $authorQry
    ";

    if ($noExplicitSubscriptions) {
        $dbResult = s_mysql_query($qry);
        if ($dbResult === false) {
            log_sql_fail();
            return [];
        }

        return mysqli_fetch_all($dbResult, MYSQLI_ASSOC);
    }

    $subjectType = \RA\SubscriptionSubjectType::fromArticleType($articleType);
    if ($subjectType === null) {
        return [];
    }

    return getSubscribersOf(
        $subjectType,
        $articleID,
        (1 << 0),  // code suggests the value of $reqWebsitePrefs should be used, but the feature is disabled for now
        $qry
    );
}

function getFeed($user, $maxMessages, $offset, &$dataOut, $latestFeedID = 0, $type = 'global')
{
    sanitize_sql_inputs($user, $maxMessages, $offset, $latestFeedID);
    settype($maxMessages, "integer");
    settype($offset, "integer");

    if ($type == 'activity') {      //    Find just this activity, ONLY!
        $subquery = "act.ID = $latestFeedID ";
    } elseif ($type == 'friends') {     //    User has been provided: find my friends!
        $subquery = "act.ID > $latestFeedID AND ( act.user = '$user' OR act.user IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$user' ) )";
    } elseif ($type == 'individual') {    //    User and 'individual', just this user's feed!
        $subquery = "act.ID > $latestFeedID AND ( act.user = '$user' )";
    } else { //if( $type == 'global' )                    //    Otherwise, global feed
        $subquery = "act.ID > $latestFeedID ";
    }

    $query = "
        SELECT 
            act.ID, act.timestamp, act.activitytype, act.User, act.data, act.data2, 
            ua.RAPoints, ua.Motto,
            gd.Title AS GameTitle, gd.ID AS GameID, gd.ImageIcon AS GameIcon,
            cons.Name AS ConsoleName,
            ach.Title AS AchTitle, ach.Description AS AchDesc, ach.Points AS AchPoints, ach.BadgeName AS AchBadge,
            lb.Title AS LBTitle, lb.Description AS LBDesc, lb.Format AS LBFormat, 
            ua.User AS CommentUser, ua.Motto AS CommentMotto, ua.RAPoints AS CommentPoints, 
            c.Payload AS Comment, c.Submitted AS CommentPostedAt, c.ID AS CommentID
        FROM Activity AS act
        LEFT JOIN UserAccounts AS ua ON (ua.User = act.User AND (! ua.Untracked || ua.User = '$user'))
        LEFT JOIN LeaderboardDef AS lb ON (activitytype IN (7, 8) AND act.data = lb.ID)
        LEFT JOIN Achievements AS ach ON (activitytype IN (1, 4, 5, 9, 10) AND ach.ID = act.data)
        LEFT JOIN GameData AS gd ON (activitytype IN (1, 4, 5, 9, 10) AND gd.ID = ach.GameID) 
                                        OR (activitytype IN (3, 6) AND gd.ID = act.data) 
                                        OR (activitytype IN (7, 8) AND gd.ID = lb.GameID)
        LEFT JOIN Console AS cons ON cons.ID = gd.ConsoleID
        LEFT JOIN Comment AS c ON c.ArticleID = act.ID
        WHERE $subquery
        ORDER BY act.ID DESC
        LIMIT $offset, $maxMessages";

    // slow on mysql 8, slow on 7.5:
    // WHERE ( !ua.Untracked || ua.User='$user' ) AND $subquery

    // works on 7.5 and on 8
    // LEFT JOIN UserAccounts AS ua ON (ua.User = act.User AND (!ua.Untracked || ua.User = '$user'))

    // do not add anything user (ua) related to the WHERE clause or it will re-evaluate all entries again
    // filter the results instead

    //error_log( $query );

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = [];

        $i = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$i] = $db_entry;
            $i++;
        }

        return $i;
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " Failed! user=$user");
    }

    return 0;
}

function getRecentlyPlayedGames($user, $offset, $count, &$dataOut)
{
    sanitize_sql_inputs($user, $offset, $count);

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

    $dataOut = [];
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numFound] = $data;
            //$dataOut[$data['GameID']] = $data;
            $numFound++;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " had issues... $user, $offset, $count");
    }

    return $numFound;
}

function getArticleComments($articleTypeID, $articleID, $offset, $count, &$dataOut)
{
    sanitize_sql_inputs($articleTypeID, $articleID, $offset, $count);

    //    $articleTypeID
    //    1 = Game
    //    2 = Achievement
    //    3 = User
    //    4 = News (unused)
    //    5 = feed Activity
    //    6 = LB
    //    7 = Ticket

    $dataOut = [];

    $numArticleComments = 0;

    $query = "
        SELECT ua.User, ua.RAPoints, LatestComments.ID, LatestComments.UserID, LatestComments.Payload AS CommentPayload, UNIX_TIMESTAMP(LatestComments.Submitted) AS Submitted, LatestComments.Edited
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
        // error_log(__FUNCTION__ . " failed, $articleTypeID, $articleID, $offset, $count ");
        log_sql_fail();
    }

    //    Fetch the last elements by submitted, but return them here in top-down order.
    $dataOut = array_reverse($dataOut);

    return $numArticleComments;
}

function isUserSubscribedToArticleComments($articleType, $articleID, $userID)
{
    sanitize_sql_inputs($articleType, $articleID, $userID);

    $subjectType = \RA\SubscriptionSubjectType::fromArticleType($articleType);

    if ($subjectType === null) {
        return false;
    }

    return isUserSubscribedTo(
        $subjectType,
        $articleID,
        $userID,
        "
            SELECT DISTINCT ua.*
            FROM
                Comment AS c
                LEFT JOIN UserAccounts AS ua ON ua.ID = c.UserID
            WHERE
                c.ArticleType = $articleType
                AND c.ArticleID = $articleID
                AND c.UserID = $userID
        "
    );
}

function getCurrentlyOnlinePlayers()
{
    $recentMinutes = 10;

    $playersFound = [];

    //    Select all users active in the last 10 minutes:
    $query = "SELECT ua.User, ua.RAPoints, act.timestamp AS LastActivityAt, ua.RichPresenceMsg AS LastActivity, act.data as GameID
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
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed3");
    }

    return $playersFound;
}

function getLatestRichPresenceUpdates()
{
    $playersFound = [];

    $recentMinutes = 10;
    $permissionsCutoff = Permissions::Registered;

    $query = "SELECT ua.User, ua.RAPoints, ua.RichPresenceMsg, gd.ID AS GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleName
              FROM UserAccounts AS ua
              LEFT JOIN GameData AS gd ON gd.ID = ua.LastGameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ua.RichPresenceMsgDate > TIMESTAMPADD( MINUTE, -$recentMinutes, NOW() )
                AND ua.LastGameID !=0
                AND ua.Permissions >= $permissionsCutoff";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['GameID'], 'integer');
            settype($db_entry['RAPoints'], 'integer');
            $playersFound[] = $db_entry;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed3: user:$user gameID:$gameID");
    }

    return $playersFound;
}

function getLatestNewAchievements($numToFetch, &$dataOut)
{
    sanitize_sql_inputs($numToFetch);

    $numFound = 0;

    $query = "SELECT ach.ID, ach.GameID, ach.Title, ach.Description, ach.Points, gd.Title AS GameTitle, gd.ImageIcon as GameIcon, ach.DateCreated, UNIX_TIMESTAMP(ach.DateCreated) AS timestamp, ach.BadgeName, c.Name AS ConsoleName
              FROM Achievements AS ach
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ach.Flags = 3
              ORDER BY DateCreated DESC
              LIMIT 0, $numToFetch ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numFound] = $db_entry;
            $numFound++;
        }
    } else {
        log_sql_fail();
        // error_log(__FUNCTION__ . " failed: $numToFetch");
    }

    return $numFound;
}

function GetMostPopularTitles($daysRange = 7, $offset = 0, $count = 10)
{
    sanitize_sql_inputs($daysRange, $offset, $count);

    $data = [];

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
