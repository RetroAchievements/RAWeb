<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use LegacyApp\Community\Enums\ActivityType;
use LegacyApp\Community\Enums\ArticleType;
use LegacyApp\Community\Models\Comment;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;
use LegacyApp\Support\Database\Models\DeletedModels;

function getMostRecentActivity($user, $type, $data): ?array
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

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return null;
    }

    return mysqli_fetch_assoc($dbResult);
}

function updateActivity($activityID): void
{
    sanitize_sql_inputs($activityID);

    // Update the last update value of given activity
    $query = "UPDATE Activity
              SET Activity.lastupdate = NOW()
              WHERE Activity.ID = $activityID ";

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();
    }
}

function RecentlyPostedCompletionActivity($user, $gameID, $isHardcore): bool
{
    sanitize_sql_inputs($user, $gameID, $isHardcore);
    settype($isHardcore, 'integer');

    $query = "SELECT act.ID
              FROM Activity AS act
              WHERE act.user='$user' AND act.activitytype=" . ActivityType::CompleteGame . "
              AND act.data='$gameID' AND act.data2='$isHardcore'
              AND act.lastupdate >= DATE_SUB( NOW(), INTERVAL 1 HOUR )
              LIMIT 1";

    $dbResult = s_mysql_query($query);

    return mysqli_num_rows($dbResult) > 0;
}

function postActivity($userIn, $activity, $customMsg, $isalt = null): bool
{
    $db = getMysqliConnection();

    $user = validateUsername($userIn);
    if (!$user) {
        return false;
    }

    userActivityPing($user);

    // Remove single quotes!
    $customMsg = str_replace("'", "''", $customMsg);
    $query = "INSERT INTO Activity (lastupdate, activitytype, user, data, data2) VALUES ";

    switch ($activity) {
        case ActivityType::EarnedAchievement:
            $achID = $customMsg;
            $query .= "(NOW(), $activity, '$user', '$achID', $isalt )";
            break;

        case ActivityType::Login:
            $lastLoginActivity = getMostRecentActivity($user, $activity, null);
            if ($lastLoginActivity) {
                $nowTimestamp = time();
                $lastLoginTimestamp = strtotime($lastLoginActivity['timestamp']);
                $diff = $nowTimestamp - $lastLoginTimestamp;

                /*
                 * record login activity only every 6 hours
                 */
                if ($diff < 60 * 60 * 6) {
                    /*
                     * new login activity from $user, duplicate of recent login " . ($diff/60) . " mins ago,
                     * ignoring!
                     */
                    return true;
                }
            }
            $query .= "(NOW(), $activity, '$user', NULL, NULL)";
            break;

        case ActivityType::StartedPlaying:
            if (!is_numeric($customMsg)) {
                return false;
            }
            $gameID = (int) $customMsg;

            /*
             * Switch the rich presence to the new game immediately
             */
            if (!getGameTitleFromID($gameID, $gameTitle, $consoleIDOut, $consoleName, $forumTopicID, $gameData)) {
                return false;
            }
            UpdateUserRichPresence($user, $gameID, "Playing $gameTitle");

            /**
             * Check for recent duplicate (check cache first, then query DB)
             */
            $lastPlayedTimestamp = null;
            $activityID = null;
            $recentlyPlayedGames = Cache::get("user:$user:recentGames");
            if (!empty($recentlyPlayedGames)) {
                foreach ($recentlyPlayedGames as $recentlyPlayedGame) {
                    if ($recentlyPlayedGame['GameID'] == $gameID) {
                        $activityID = $recentlyPlayedGame['ActivityID'];
                        $lastPlayedTimestamp = strtotime($recentlyPlayedGame['LastPlayed']);
                        break;
                    }
                }
            }

            if ($activityID === null) {
                // not in recent activity, look back farther
                $lastPlayedActivityData = getMostRecentActivity($user, $activity, $gameID);
                if (isset($lastPlayedActivityData)) {
                    $lastPlayedTimestamp = strtotime($lastPlayedActivityData['timestamp']);
                    $activityID = $lastPlayedActivityData['ID'];
                }
            }

            if ($activityID !== null) {
                $diff = time() - $lastPlayedTimestamp;

                /*
                 * record game session activity only every 12 hours
                 */
                if ($diff < 60 * 60 * 12) {
                    /*
                     * new playing $gameTitle activity from $user, duplicate of recent activity " . ($diff/60) . " mins ago
                     * Updating db, but not posting!
                     */
                    updateActivity($activityID);
                    expireRecentlyPlayedGames($user);

                    return true;
                } else {
                    /*
                     * recognises that $user has played $gameTitle recently, but longer than 12 hours ago (" . ($diff/60) . " mins) so still posting activity!
                     * $nowTimestamp - $lastPlayedTimestamp = $diff
                     */
                }
            }

            $query .= "(NOW(), $activity, '$user', '$gameID', NULL)";
            break;

        case ActivityType::UploadAchievement:
        case ActivityType::EditAchievement:
        case ActivityType::OpenedTicket:
        case ActivityType::ClosedTicket:
            $achID = $customMsg;
            $query .= "(NOW(), $activity, '$user', '$achID', NULL)";
            break;

        case ActivityType::CompleteGame:
            $gameID = $customMsg;
            $query .= "(NOW(), $activity, '$user', '$gameID', $isalt)";
            break;

        case ActivityType::NewLeaderboardEntry:
        case ActivityType::ImprovedLeaderboardEntry:
            $lbID = $customMsg['LBID'];
            $score = $customMsg['Score'];
            $query .= "(NOW(), $activity, '$user', '$lbID', '$score')";
            break;

        case ActivityType::Unknown:
        default:
            $query .= "(NOW(), $activity, '$user', '$customMsg', '$customMsg')";
            break;
    }

    $dbResult = mysqli_query($db, $query);
    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    if ($activity == ActivityType::StartedPlaying) {
        // have to do this after the query is executed to prevent a race condition where
        // it may get re-cached before the query finishes
        expireRecentlyPlayedGames($user);
    }

    /**
     * Update UserAccount
     */
    $newActID = mysqli_insert_id($db);
    $query = "UPDATE UserAccounts AS ua SET ua.LastActivityID = $newActID, ua.LastLogin = NOW() WHERE ua.User = '$user'";
    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    return true;
}

function userActivityPing($user): bool
{
    if (!isset($user) || mb_strlen($user) < 2) {
        return false;
    }
    sanitize_sql_inputs($user);

    $query = "UPDATE UserAccounts AS ua
              SET ua.LastLogin = NOW()
              WHERE ua.User = '$user' ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    return true;
}

function UpdateUserRichPresence($user, $gameID, $presenceMsg): bool
{
    if (!isset($user) || mb_strlen($user) < 2) {
        return false;
    }
    sanitize_sql_inputs($user, $gameID, $presenceMsg);
    settype($gameID, 'integer');

    $presenceMsg = utf8_sanitize($presenceMsg);

    $query = "UPDATE UserAccounts AS ua
              SET ua.RichPresenceMsg = '$presenceMsg', ua.LastGameID = '$gameID', ua.RichPresenceMsgDate = NOW()
              WHERE ua.User = '$user' ";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);
    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    return true;
}

function getActivityMetadata($activityID): ?array
{
    sanitize_sql_inputs($activityID);

    $query = "SELECT * FROM Activity
              WHERE ID='$activityID'";

    $dbResult = s_mysql_query($query);

    return mysqli_fetch_assoc($dbResult);
}

function RemoveComment(int $commentID, $userID, $permissions): bool
{
    settype($commentID, 'integer');
    settype($userID, 'integer');
    settype($permissions, 'integer');

    /** @var Comment $comment */
    $comment = Comment::findOrFail($commentID);

    $articleID = $comment->ArticleID;

    $query = "DELETE FROM Comment WHERE ID = $commentID";

    // if not UserWall's owner nor admin, check if it's the author
    // TODO use policies to explicitly determine ability to delete a comment instead of piggy-backing query specificity
    if ($articleID != $userID && $permissions < Permissions::Admin) {
        $query .= " AND UserID = $userID";
    }

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    /** @var User $user */
    $user = request()->user();
    DeletedModels::create([
        'ModelType' => 'Comment',
        'ModelID' => $commentID,
        'DeletedByUserID' => $user->ID,
    ]);

    return mysqli_affected_rows($db) > 0;
}

function addArticleComment($user, $articleType, $articleID, $commentPayload, $onBehalfOfUser = null): bool
{
    if (!ArticleType::isValid($articleType)) {
        return false;
    }

    sanitize_sql_inputs($activityID, $commentPayload);

    // Note: $user is the person who just made a comment.

    $userID = getUserIDFromUser($user);
    if ($userID == 0) {
        return false;
    }

    // Replace all single quotes with double quotes (to work with MYSQL DB)
    // $commentPayload = str_replace( "'", "''", $commentPayload );

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

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    // Inform Subscribers of this comment:
    if (is_array($articleID)) {
        foreach ($articleID as $id) {
            informAllSubscribersAboutActivity($articleType, $id, $user, $onBehalfOfUser);
        }
    } else {
        informAllSubscribersAboutActivity($articleType, $articleID, $user, $onBehalfOfUser);
    }

    return true;
}

function getFeed($user, $maxMessages, $offset, &$dataOut, $latestFeedID = 0, $type = 'global'): int
{
    sanitize_sql_inputs($user, $maxMessages, $offset, $latestFeedID);
    settype($maxMessages, "integer");
    settype($offset, "integer");

    if ($type == 'activity') { // Find just this activity, ONLY!
        $subquery = "act.ID = $latestFeedID ";
    } elseif ($type == 'friends') { // User has been provided: find my friends!
        $friendSubquery = GetFriendsSubquery($user);
        $subquery = "act.ID > $latestFeedID AND ( act.user IN ( $friendSubquery ) )";
    } elseif ($type == 'individual') { // User and 'individual', just this user's feed!
        $subquery = "act.ID > $latestFeedID AND ( act.user = '$user' )";
    } else { // Otherwise, global feed
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

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = [];

        $i = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$i] = $db_entry;
            $dataOut[$i]['timestamp'] = strtotime($dataOut[$i]['timestamp']);
            $dataOut[$i]['CommentPostedAt'] = strtotime($dataOut[$i]['CommentPostedAt']);
            $i++;
        }

        return $i;
    } else {
        log_sql_fail();
    }

    return 0;
}

function expireRecentlyPlayedGames(string $user): void
{
    Cache::forget("user:$user:recentGames");
}

function getRecentlyPlayedGames(string $user, int $offset, int $count, ?array &$dataOut): int
{
    if ($offset == 0 && $count <= 5) {
        $recentlyPlayedGames = Cache::remember("user:$user:recentGames", Carbon::now()->addDays(30), fn () => _getRecentlyPlayedGameIds($user, 0, 5));
    } else {
        $recentlyPlayedGames = _getRecentlyPlayedGameIds($user, $offset, $count);
    }

    $numFound = 0;
    $dataOut = [];

    if (!empty($recentlyPlayedGames)) {
        $recentlyPlayedGameIDs = [];
        foreach ($recentlyPlayedGames as $recentlyPlayedGame) {
            $recentlyPlayedGameIDs[] = $recentlyPlayedGame['GameID'];
        }

        // discard anything that's not numeric or the query will fail
        $recentlyPlayedGameIDs = collect($recentlyPlayedGameIDs)
            ->filter(fn ($id) => is_int($id) || is_numeric($id))
            ->implode(',');
        $query = "SELECT gd.ID AS GameID, gd.ConsoleID, c.Name AS ConsoleName, gd.Title, gd.ImageIcon
                  FROM GameData AS gd LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                  WHERE gd.ID IN ($recentlyPlayedGameIDs)";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $gameData = [];
            while ($data = mysqli_fetch_assoc($dbResult)) {
                $gameData[$data['GameID']] = $data;
            }

            foreach ($recentlyPlayedGames as $recentlyPlayedGame) {
                $gameID = $recentlyPlayedGame['GameID'];
                if (array_key_exists($gameID, $gameData)) {
                    $gameData[$gameID]['LastPlayed'] = $recentlyPlayedGame['LastPlayed'];
                    $dataOut[] = $gameData[$gameID];
                    $numFound++;
                }
            }
        } else {
            log_sql_fail();
        }
    }

    return $numFound;
}

function _getRecentlyPlayedGameIDs(string $user, int $offset, int $count): array
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
    // 19:30 02/02/2014 rewritten without MAX() and using an inner query. ~300% faster but I don't know why... :(
    // 02:51 03/02/2014 re-rewritten with MAX()
    // 01:38 15/02/2014 re-readded 'AND act.activitytype = 3' to inner query. act.data is not necessarily a game, therefore we need this '3' part.
    // 22:56 18/02/2014 re-re-readded 'MAX() to inner.
    // 08:05 01/10/2014 removed outer activitytype=3, added rating
    // {$RatingType::Game}

    $query = "
 SELECT MAX(act.ID) as ActivityID, MAX(act.lastupdate) AS LastPlayed, act.data as GameID
 FROM Activity AS act
 WHERE act.user='$user' AND act.activitytype = " . ActivityType::StartedPlaying . "
 GROUP BY act.data
 ORDER BY MAX( act.lastupdate ) DESC
 LIMIT $offset, $count";

    $recentlyPlayedGames = [];

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $recentlyPlayedGames[] = $data;
        }
    } else {
        log_sql_fail();
    }

    return $recentlyPlayedGames;
}

function getArticleComments(int $articleTypeID, int $articleID, int $offset, int $count, ?array &$dataOut, bool $recent = false): int
{
    sanitize_sql_inputs($articleTypeID, $articleID, $offset, $count);

    $dataOut = [];
    $numArticleComments = 0;
    $order = $recent ? ' DESC' : '';

    $query = "SELECT SQL_CALC_FOUND_ROWS ua.User, ua.RAPoints, c.ID, c.UserID,
                     c.Payload AS CommentPayload,
                     UNIX_TIMESTAMP(c.Submitted) AS Submitted, c.Edited
              FROM Comment AS c
              LEFT JOIN UserAccounts AS ua ON ua.ID = c.UserID
              WHERE c.ArticleType=$articleTypeID AND c.ArticleID=$articleID
              ORDER BY c.Submitted$order, c.ID$order
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$numArticleComments] = $db_entry;
            $numArticleComments++;
        }

        if ($offset != 0 || $numArticleComments >= $count) {
            $query = "SELECT FOUND_ROWS() AS NumResults";
            $dbResult = s_mysql_query($query);
            if ($dbResult !== false) {
                $numArticleComments = mysqli_fetch_assoc($dbResult)['NumResults'];
            }
        }
    } else {
        log_sql_fail();
    }

    return $numArticleComments;
}

function getRecentArticleComments(int $articleTypeID, int $articleID, ?array &$dataOut, int $count = 20): int
{
    $numArticleComments = getArticleComments($articleTypeID, $articleID, 0, $count, $dataOut, true);

    // Fetch the last elements by submitted, but return them here in top-down order.
    $dataOut = array_reverse($dataOut);

    return $numArticleComments;
}

function getLatestRichPresenceUpdates(): array
{
    $playersFound = [];

    $recentMinutes = 10;
    $permissionsCutoff = Permissions::Registered;

    $query = "SELECT ua.User, ua.RAPoints, ua.RASoftcorePoints, ua.RichPresenceMsg,
                     gd.ID AS GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleName
              FROM UserAccounts AS ua
              LEFT JOIN GameData AS gd ON gd.ID = ua.LastGameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ua.RichPresenceMsgDate > TIMESTAMPADD( MINUTE, -$recentMinutes, NOW() )
                AND ua.LastGameID !=0
                AND ua.Permissions >= $permissionsCutoff
              ORDER BY ua.RAPoints DESC";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            settype($db_entry['GameID'], 'integer');
            settype($db_entry['RAPoints'], 'integer');
            settype($db_entry['RASoftcorePoints'], 'integer');
            $playersFound[] = $db_entry;
        }
    } else {
        log_sql_fail();
    }

    return $playersFound;
}

function getLatestNewAchievements($numToFetch, &$dataOut): int
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
    }

    return $numFound;
}

function GetMostPopularTitles($daysRange = 7, $offset = 0, $count = 10): array
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

function getUserGameActivity(string $user, int $gameID): array
{
    sanitize_sql_inputs($user);

    $query = "SELECT a.timestamp, a.lastupdate, a.data
              FROM Activity a
              WHERE a.User='$user' AND a.data=$gameID
              AND a.activitytype=" . ActivityType::StartedPlaying;
    $dbResult = s_mysql_query($query);
    if ($dbResult === false) {
        log_sql_fail();

        return [];
    }

    $sessions = [];
    while ($row = mysqli_fetch_assoc($dbResult)) {
        $sessions[] = [
            'StartTime' => strtotime($row['timestamp']),
        ];

        if ($row['lastupdate'] != $row['timestamp']) {
            $sessions[] = [
                'StartTime' => strtotime($row['lastupdate']),
            ];
        }
    }

    // create a dummy placeholder session for any achievements unlocked before the first session
    $sessions[] = [
        'StartTime' => 0,
        'IsGenerated' => true,
    ];

    // reverse sort by date so we can update the appropriate session when we find it
    usort($sessions, function ($a, $b) { return $b['StartTime'] - $a['StartTime']; });

    $query = "SELECT a.timestamp, a.data, a.data2, ach.Title, ach.Points, ach.BadgeName, ach.Flags
              FROM Activity a
              LEFT JOIN Achievements ach ON ach.ID = a.data
              WHERE ach.GameID=$gameID AND a.User='$user'
              AND a.activitytype=" . ActivityType::EarnedAchievement;
    $dbResult = s_mysql_query($query);
    if ($dbResult === false) {
        log_sql_fail();

        return [];
    }

    $achievements = [];
    $unofficialAchievements = [];
    while ($row = mysqli_fetch_assoc($dbResult)) {
        $when = strtotime($row['timestamp']);
        $achievements[$row['data']] = $when;

        if ($row['Flags'] != AchievementType::OfficialCore) {
            $unofficialAchievements[$row['data']] = 1;
        }

        foreach ($sessions as &$session) {
            if ($session['StartTime'] < $when) {
                $session['Achievements'][] = [
                    'When' => $when,
                    'AchievementID' => $row['data'],
                    'Title' => $row['Title'],
                    'Points' => $row['Points'],
                    'BadgeName' => $row['BadgeName'],
                    'Flags' => $row['Flags'],
                    'HardcoreMode' => $row['data2'],
                ];
                break;
            }
        }
    }

    // calculate the duration of each session
    $totalTime = _updateUserGameSessionDurations($sessions, $achievements);

    // sort everything and find the first and last achievement timestamps
    usort($sessions, function ($a, $b) { return $a['StartTime'] - $b['StartTime']; });

    $unlockSessionCount = 0;
    $firstAchievementTime = null;
    $lastAchievementTime = null;
    foreach ($sessions as &$session) {
        if (!empty($session['Achievements'])) {
            $unlockSessionCount++;
            foreach ($session['Achievements'] as &$achievement) {
                if ($firstAchievementTime === null) {
                    $firstAchievementTime = $achievement['When'];
                }
                $lastAchievementTime = $achievement['When'];
            }
        }
    }

    // assume every achievement took roughly the same amount of time to earn. divide the
    // user's total known playtime by the number of achievements they've earned to get the
    // approximate time per achievement earned. add this value to each session to account
    // for time played after getting the last achievement of the session.
    $achievementsUnlocked = count($achievements);
    if ($achievementsUnlocked > 0 && $unlockSessionCount > 1) {
        $sessionAdjustment = $totalTime / $achievementsUnlocked;
        $totalTime += $sessionAdjustment * $unlockSessionCount;
    } else {
        $sessionAdjustment = 0;
    }

    $activity = [
        'Sessions' => $sessions,
        'TotalTime' => $totalTime,
        'PerSessionAdjustment' => $sessionAdjustment,
        'AchievementsUnlocked' => count($achievements) - count($unofficialAchievements),
        'UnlockSessionCount' => $unlockSessionCount,
        'FirstUnlockTime' => $firstAchievementTime,
        'LastUnlockTime' => $lastAchievementTime,
        'TotalUnlockTime' => ($lastAchievementTime != null) ? $lastAchievementTime - $firstAchievementTime : 0,
    ];

    // Count num possible achievements
    $query = "SELECT COUNT(*) as Count FROM Achievements ach
              WHERE ach.Flags=" . AchievementType::OfficialCore . " AND ach.GameID=$gameID";
    $dbResult = s_mysql_query($query);
    if ($dbResult) {
        $activity['CoreAchievementCount'] = mysqli_fetch_assoc($dbResult)['Count'];
    }

    return $activity;
}

function _updateUserGameSessionDurations(array &$sessions, array &$achievements): int
{
    $totalTime = 0;
    $newSessions = [];
    foreach ($sessions as &$session) {
        if (!array_key_exists('Achievements', $session)) {
            if ($session['StartTime'] > 0) {
                $session['Achievements'] = [];
                $session['EndTime'] = $session['StartTime'];
                $newSessions[] = $session;
            }
        } else {
            usort($session['Achievements'], function ($a, $b) { return $a['When'] - $b['When']; });

            if ($session['StartTime'] === 0) {
                $session['StartTime'] = $session['Achievements'][0]['When'];
            }

            foreach ($session['Achievements'] as &$achievement) {
                if ($achievement['When'] != $achievements[$achievement['AchievementID']]) {
                    $achievement['UnlockedLater'] = true;
                }
            }

            // if there are any gaps in the achievements earned within a session that
            // are more than four hours apart, split into separate sessions
            $split = [];
            $prevTime = $session['StartTime'];
            for ($i = 0; $i < count($session['Achievements']); $i++) {
                $distance = $session['Achievements'][$i]['When'] - $prevTime;
                if ($distance > 4 * 60 * 60) {
                    $split[] = $i;
                }
                $prevTime = $session['Achievements'][$i]['When'];
            }

            if (empty($split)) {
                $session['EndTime'] = end($session['Achievements'])['When'];
                $totalTime += ($session['EndTime'] - $session['StartTime']);
                $newSessions[] = $session;
            } else {
                $split[] = count($session['Achievements']);
                $firstIndex = 0;
                $isGenerated = false;
                foreach ($split as $i) {
                    if ($i === 0) {
                        $newSession = [
                            'StartTime' => $session['StartTime'],
                            'EndTime' => $session['StartTime'],
                            'Achievements' => [],
                        ];
                    } else {
                        $newSession = [
                            'StartTime' => !$isGenerated ? $session['StartTime'] :
                                $session['Achievements'][$firstIndex]['When'],
                            'EndTime' => $session['Achievements'][$i - 1]['When'],
                            'Achievements' => array_slice($session['Achievements'], $firstIndex, $i - $firstIndex),
                        ];
                    }

                    $newSession['IsGenerated'] = $isGenerated;
                    $isGenerated = true;

                    $totalTime += ($newSession['EndTime'] - $newSession['StartTime']);
                    $newSessions[] = $newSession;

                    $firstIndex = $i;
                }
            }
        }
    }

    $sessions = $newSessions;

    return $totalTime;
}
