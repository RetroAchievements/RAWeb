<?php

use App\Community\Enums\ActivityType;
use App\Community\Enums\ArticleType;
use App\Community\Models\Comment;
use App\Community\Models\UserActivityLegacy;
use App\Platform\Enums\AchievementType;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use App\Support\Cache\CacheKey;
use App\Support\Database\Models\DeletedModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

function getMostRecentActivity(string $user, ?int $type = null, ?int $data = null): ?array
{
    $innerClause = "Activity.user = :user";
    if (isset($type)) {
        $innerClause .= " AND Activity.activityType = $type";
    }
    if (isset($data)) {
        $innerClause .= " AND Activity.data = $data";
    }

    $query = "SELECT * FROM Activity AS act
              WHERE act.ID =
                ( SELECT MAX(Activity.ID) FROM Activity WHERE $innerClause ) ";

    return legacyDbFetch($query, ['user' => $user]);
}

function updateActivity(int $activityID): void
{
    // Update the last update value of given activity
    $query = "UPDATE Activity
              SET Activity.lastupdate = NOW()
              WHERE Activity.ID = $activityID ";

    legacyDbStatement($query);
}

function RecentlyPostedCompletionActivity(string $user, int $gameID, int $isHardcore): bool
{
    sanitize_sql_inputs($user);

    $query = "SELECT act.ID
              FROM Activity AS act
              WHERE act.user='$user' AND act.activitytype=" . ActivityType::CompleteGame . "
              AND act.data='$gameID' AND act.data2='$isHardcore'
              AND act.lastupdate >= DATE_SUB( NOW(), INTERVAL 1 HOUR )
              LIMIT 1";

    $dbResult = s_mysql_query($query);

    return mysqli_num_rows($dbResult) > 0;
}

function postActivity(string $userIn, int $type, ?int $data = null, ?int $data2 = null): bool
{
    if (!ActivityType::isValid($type)) {
        return false;
    }

    $user = User::firstWhere('User', $userIn);
    if ($user === null) {
        return false;
    }

    $activity = new UserActivityLegacy([
        'User' => $user->User,
        'activitytype' => $type,
    ]);

    switch ($type) {
        case ActivityType::UnlockedAchievement:
            if ($data === null) {
                return false;
            }
            $activity->data = (string) $data;
            break;

        case ActivityType::Login:
            $lastLoginActivity = getMostRecentActivity($user->User, $type);
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
            break;

        case ActivityType::StartedPlaying:
            if ($data === null) {
                return false;
            }
            $gameID = $data;

            /*
             * Switch the rich presence to the new game immediately
             */
            $game = getGameData($gameID);
            if (!$game) {
                return false;
            }

            UpdateUserRichPresence($user, $gameID, "Playing {$game['Title']}");

            /**
             * Check for recent duplicate (check cache first, then query DB)
             */
            $lastPlayedTimestamp = null;
            $activityID = null;
            $recentlyPlayedGamesCacheKey = CacheKey::buildUserRecentGamesCacheKey($user);
            $recentlyPlayedGames = Cache::get($recentlyPlayedGamesCacheKey);
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
                $lastPlayedActivityData = getMostRecentActivity($user->User, $type, $gameID);
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
                    expireRecentlyPlayedGames($user->User);

                    return true;
                }
                /*
                 * recognises that $user has played $gameTitle recently, but longer than 12 hours ago (" . ($diff/60) . " mins) so still posting activity!
                 * $nowTimestamp - $lastPlayedTimestamp = $diff
                 */
            }

            $activity->data = (string) $gameID;
            break;

        case ActivityType::UploadAchievement:
        case ActivityType::EditAchievement:
        case ActivityType::OpenedTicket:
        case ActivityType::ClosedTicket:
            $activity->data = (string) $data;
            break;

        case ActivityType::CompleteGame:
        case ActivityType::NewLeaderboardEntry:
        case ActivityType::ImprovedLeaderboardEntry:
            $activity->data = (string) $data;
            $activity->data2 = (string) $data2;
            break;
    }

    $activity->save();

    if ($type == ActivityType::StartedPlaying) {
        // have to do this after the activity is saved to prevent a race condition where
        // it may get re-cached before the activity is committed.
        expireRecentlyPlayedGames($user->User);
    }

    // update UserAccount
    $user->LastLogin = Carbon::now();
    $user->LastActivityID = $activity->ID;
    $user->timestamps = false;
    $user->save();

    return true;
}

function UpdateUserRichPresence(User $user, int $gameID, string $presenceMsg): void
{
    $user->RichPresenceMsg = utf8_sanitize($presenceMsg);
    $user->LastGameID = $gameID;
    $user->RichPresenceMsgDate = Carbon::now();
}

function getActivityMetadata(int $activityID): ?array
{
    $query = "SELECT * FROM Activity
              WHERE ID='$activityID'";

    return legacyDbFetch($query);
}

function RemoveComment(int $commentID, int $userID, int $permissions): bool
{
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

function getIsCommentDoublePost(int $userID, array|int $articleID, string $commentPayload): bool
{
    $query = "SELECT Comment.Payload, Comment.ArticleID
        FROM Comment
        WHERE UserID = :userId
        ORDER BY Comment.Submitted DESC
        LIMIT 1";

    $dbResult = legacyDbFetch($query, ['userId' => $userID]);

    // Otherwise the user can't make their first post.
    if (!$dbResult) {
        return false;
    }

    $retrievedPayload = $dbResult['Payload'];
    $retrievedArticleID = $dbResult['ArticleID'];

    return
        $retrievedPayload === $commentPayload
        && $retrievedArticleID === $articleID
    ;
}

function addArticleComment(
    string $user,
    int $articleType,
    array|int $articleID,
    string $commentPayload,
    ?string $onBehalfOfUser = null,
): bool {
    if (!ArticleType::isValid($articleType)) {
        return false;
    }

    sanitize_sql_inputs($commentPayload);

    // Note: $user is the person who just made a comment.

    $userID = getUserIDFromUser($user);
    if ($userID == 0) {
        return false;
    }

    if ($user !== "Server" && getIsCommentDoublePost($userID, $articleID, $commentPayload)) {
        // Fail silently.
        return true;
    }

    // Replace all single quotes with double quotes (to work with MYSQL DB)
    // $commentPayload = str_replace( "'", "''", $commentPayload );

    if (is_array($articleID)) {
        $articleIDs = $articleID;
        $arrayCount = count($articleID);
        $count = 0;
        $query = "INSERT INTO Comment (ArticleType, ArticleID, UserID, Payload) VALUES";
        foreach ($articleID as $id) {
            $query .= "( $articleType, $id, $userID, '$commentPayload' )";
            if (++$count !== $arrayCount) {
                $query .= ",";
            }
        }
    } else {
        $query = "INSERT INTO Comment (ArticleType, ArticleID, UserID, Payload) VALUES( $articleType, $articleID, $userID, '$commentPayload' )";
        $articleIDs = [$articleID];
    }

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

    // Inform Subscribers of this comment:
    foreach ($articleIDs as $id) {
        $query = "SELECT MAX(ID) AS CommentID FROM Comment
                  WHERE ArticleType=$articleType AND ArticleID=$id AND UserID=$userID";
        $commentID = legacyDbFetch($query)['CommentID'];

        informAllSubscribersAboutActivity($articleType, $id, $user, $commentID, $onBehalfOfUser);
    }

    return true;
}

function expireRecentlyPlayedGames(string $user): void
{
    $userRecentGamesCacheKey = CacheKey::buildUserRecentGamesCacheKey($user);
    Cache::forget($userRecentGamesCacheKey);
}

function getRecentlyPlayedGames(string $user, int $offset, int $count, ?array &$dataOut): int
{
    if ($offset == 0 && $count <= 5) {
        $userRecentGamesCacheKey = CacheKey::buildUserRecentGamesCacheKey($user);
        $recentlyPlayedGames = Cache::remember($userRecentGamesCacheKey, Carbon::now()->addDays(30), fn () => _getRecentlyPlayedGameIds($user, 0, 5));
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

        // cache may remember more than was asked for
        if ($count < count($recentlyPlayedGameIDs)) {
            $recentlyPlayedGameIDs = array_slice($recentlyPlayedGameIDs, 0, $count);
        }

        // discard anything that's not numeric or the query will fail
        $recentlyPlayedGameIDs = collect($recentlyPlayedGameIDs)
            ->filter(fn ($id) => is_int($id) || is_numeric($id))
            ->implode(',');
        if (empty($recentlyPlayedGameIDs)) {
            return 0;
        }

        $query = "SELECT gd.ID AS GameID, gd.ConsoleID, c.Name AS ConsoleName, gd.Title, gd.ImageIcon
                  FROM GameData AS gd LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                  WHERE gd.ID IN ($recentlyPlayedGameIDs)";

        $gameData = [];
        $dbResult = legacyDbFetchAll($query);
        foreach ($dbResult as $data) {
            settype($data['GameID'], 'integer');
            settype($data['ConsoleID'], 'integer');
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
    }

    return $numFound;
}

function _getRecentlyPlayedGameIDs(string $user, int $offset, int $count): array
{
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

    // TODO slow query (15)
    // $games = User::find($user->ID)->playerGames()->orderByDesc('last_played_at')->get();
    // $games = User::find($user->ID)->achievementSets()->get();
    $query = "
        SELECT MAX(act.ID) as ActivityID, MAX(act.lastupdate) AS LastPlayed, act.data as GameID
        FROM Activity AS act
        WHERE act.user=:username AND act.activitytype = " . ActivityType::StartedPlaying . "
        GROUP BY act.data
        ORDER BY MAX( act.lastupdate ) DESC
        LIMIT $offset, $count";

    return legacyDbFetchAll($query, ['username' => $user])->toArray();
}

function getArticleComments(
    int $articleTypeID,
    int $articleID,
    int $offset,
    int $count,
    ?array &$dataOut,
    bool $recent = false
): int {
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

    return (int) $numArticleComments;
}

function getRecentArticleComments(
    int $articleTypeID,
    int $articleID,
    ?array &$dataOut,
    int $count = 20
): int {
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

    $query = "SELECT ua.User, IF(ua.Untracked, 0, ua.RAPoints) as RAPoints, IF(ua.Untracked, 0, ua.RASoftcorePoints) as RASoftcorePoints,
                     ua.RichPresenceMsg, gd.ID AS GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleName
              FROM UserAccounts AS ua
              LEFT JOIN GameData AS gd ON gd.ID = ua.LastGameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ua.RichPresenceMsgDate > TIMESTAMPADD( MINUTE, -$recentMinutes, NOW() )
                AND ua.LastGameID != 0
                AND ua.Permissions >= $permissionsCutoff
              ORDER BY RAPoints DESC, RASoftcorePoints DESC, ua.User ASC";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $db_entry['GameID'] = (int) $db_entry['GameID'];
            $db_entry['RAPoints'] = (int) $db_entry['RAPoints'];
            $db_entry['RASoftcorePoints'] = (int) $db_entry['RASoftcorePoints'];
            $playersFound[] = $db_entry;
        }
    } else {
        log_sql_fail();
    }

    return $playersFound;
}

function getLatestNewAchievements(int $numToFetch, ?array &$dataOut): int
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

function GetMostPopularTitles(int $daysRange = 7, int $offset = 0, int $count = 10): array
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

        if ($row['lastupdate'] !== $row['timestamp']) {
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
    usort($sessions, fn ($a, $b) => $b['StartTime'] - $a['StartTime']);

    $query = "SELECT a.timestamp, a.data, a.data2, ach.Title, ach.Description, ach.Points, ach.BadgeName, ach.Flags
              FROM Activity a
              LEFT JOIN Achievements ach ON ach.ID = a.data
              WHERE ach.GameID=$gameID AND a.User='$user'
              AND a.activitytype=" . ActivityType::UnlockedAchievement;
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
                    'Description' => $row['Description'],
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
    usort($sessions, fn ($a, $b) => $a['StartTime'] - $b['StartTime']);

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

function _updateUserGameSessionDurations(array &$sessions, array $achievements): int
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
            usort($session['Achievements'], fn ($a, $b) => $a['When'] - $b['When']);

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
            $itemsCount = count($session['Achievements']);
            for ($i = 0; $i < $itemsCount; $i++) {
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
                            'StartTime' => $isGenerated ? $session['Achievements'][$firstIndex]['When'] :
                                $session['StartTime'],
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
