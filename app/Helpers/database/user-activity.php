<?php

use App\Community\Enums\ActivityType;
use App\Community\Enums\ArticleType;
use App\Community\Models\Comment;
use App\Community\Models\UserActivityLegacy;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerSession;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use App\Support\Cache\CacheKey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @deprecated see WriteUserActivity listener
 */
function postActivity(string|User $userIn, int $type, ?int $data = null, ?int $data2 = null): bool
{
    if (!ActivityType::isValid($type)) {
        return false;
    }

    if ($userIn instanceof User) {
        $user = $userIn;
    } else {
        $user = User::firstWhere('User', $userIn);
        if ($user === null) {
            return false;
        }
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
            $activity->data2 = (string) $data2;
            break;

        case ActivityType::Login:
            /* only record login activity every six hours */
            $cacheKey = CacheKey::buildUserLastLoginCacheKey($user->User);
            $lastLogin = Cache::get($cacheKey);
            if ($lastLogin && $lastLogin > Carbon::now()->subHours(6)) {
                /* ignore event, login recorded recently */
                return true;
            }
            Cache::put($cacheKey, Carbon::now(), Carbon::now()->addHours(6));
            break;

        case ActivityType::StartedPlaying:
            if ($data === null) {
                return false;
            }

            $game = getGameData($data);
            if (!$game) {
                return false;
            }

            $activity->data = (string) $data;
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

    // update UserAccount
    $user->LastLogin = Carbon::now();
    $user->LastActivityID = $activity->ID;
    $user->save();

    return true;
}

/**
 * @deprecated see UserActivity model
 */
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
    if ($articleID != $userID && $permissions < Permissions::Moderator) {
        $query .= " AND UserID = $userID";
    }

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    if (!$dbResult) {
        log_sql_fail();

        return false;
    }

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

    // Note: $user is the person who just made a comment.

    $userID = getUserIDFromUser($user);
    if ($userID == 0) {
        return false;
    }

    if ($user !== "Server" && getIsCommentDoublePost($userID, $articleID, $commentPayload)) {
        // Fail silently.
        return true;
    }

    if (is_array($articleID)) {
        $bindings = [];

        $articleIDs = $articleID;
        $arrayCount = count($articleID);
        $count = 0;
        $query = "INSERT INTO Comment (ArticleType, ArticleID, UserID, Payload) VALUES";
        foreach ($articleID as $id) {
            $bindings['commentPayload' . $count] = $commentPayload;
            $query .= "( $articleType, $id, $userID, :commentPayload$count )";
            if (++$count !== $arrayCount) {
                $query .= ",";
            }
        }
    } else {
        $query = "INSERT INTO Comment (ArticleType, ArticleID, UserID, Payload) VALUES( $articleType, $articleID, $userID, :commentPayload)";
        $bindings = ['commentPayload' => $commentPayload];
        $articleIDs = [$articleID];
    }

    legacyDbStatement($query, $bindings);

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
    $query = "SELECT pg.last_played_at AS LastPlayed, pg.game_id AS GameID
              FROM player_games pg
              INNER JOIN UserAccounts ua ON ua.ID = pg.user_id
              WHERE ua.User = :username
              ORDER BY pg.last_played_at desc
              LIMIT $offset, $count";

    $recentlyPlayedGames = legacyDbFetchAll($query, ['username' => $user])->toArray();

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

function getArticleComments(
    int $articleTypeID,
    int $articleID,
    int $offset,
    int $count,
    ?array &$dataOut,
    bool $recent = false
): int {
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

    $ifRAPoints = ifStatement('ua.Untracked', 0, 'ua.RAPoints');
    $ifRASoftcorePoints = ifStatement('ua.Untracked', 0, 'ua.RASoftcorePoints');
    $timestampStatement = timestampAddMinutesStatement(-$recentMinutes);

    $query = "SELECT ua.User, $ifRAPoints as RAPoints, $ifRASoftcorePoints as RASoftcorePoints,
                     ua.RichPresenceMsg, gd.ID AS GameID, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, c.Name AS ConsoleName
              FROM UserAccounts AS ua
              LEFT JOIN GameData AS gd ON gd.ID = ua.LastGameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE ua.RichPresenceMsgDate > $timestampStatement
                AND ua.LastGameID != 0
                AND ua.Permissions >= $permissionsCutoff
              ORDER BY RAPoints DESC, RASoftcorePoints DESC, ua.User ASC";

    $dbResult = legacyDbFetchAll($query);

    if ($dbResult !== false) {
        foreach ($dbResult as $dbEntry) {
            $dbEntry['GameID'] = (int) $dbEntry['GameID'];
            $dbEntry['RAPoints'] = (int) $dbEntry['RAPoints'];
            $dbEntry['RASoftcorePoints'] = (int) $dbEntry['RASoftcorePoints'];
            $playersFound[] = $dbEntry;
        }
    }

    return $playersFound;
}

function getLatestNewAchievements(int $numToFetch, ?array &$dataOut): int
{
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

function getUserGameActivity(string $username, int $gameID): array
{
    $user = User::firstWhere('User', $username);
    if (!$user) {
        return [];
    }

    $game = Game::firstWhere('ID', $gameID);
    if (!$game) {
        return [];
    }

    $achievements = [];
    $unofficialAchievements = [];
    $sessions = [];

    $playerSessions = PlayerSession::where('user_id', '=', $user->ID)
        ->where('game_id', '=', $gameID)
        ->get();
    foreach ($playerSessions as $playerSession) {
        $session = [
            'StartTime' => $playerSession->created_at->unix(),
            'EndTime' => $playerSession->updated_at->unix(),
            'IsGenerated' => $playerSession->created_at < Carbon::create(2023, 10, 14, 13, 16, 42),
            'Achievements' => [],
        ];
        if (!empty($playerSession->rich_presence)) {
            $session['RichPresence'] = $playerSession->rich_presence;
            $session['RichPresenceTime'] = $playerSession->rich_presence_updated_at->unix();
        }
        $sessions[] = $session;
    }

    // reverse sort by date so we can update the appropriate session when we find it
    usort($sessions, fn ($a, $b) => $b['StartTime'] - $a['StartTime']);

    $addAchievementToSession = function (&$sessions, $playerAchievement, $when, $hardcore): void {
        $createSessionAchievement = function ($playerAchievement, $when, $hardcore): array {
            return [
                'When' => $when,
                'AchievementID' => $playerAchievement->achievement_id,
                'HardcoreMode' => $hardcore,
                'Flags' => $playerAchievement->Flags,
                // used by avatar function to avoid additional query
                'Title' => $playerAchievement->Title,
                'Description' => $playerAchievement->Description,
                'Points' => $playerAchievement->Points,
                'BadgeName' => $playerAchievement->BadgeName,
            ];
        };

        $maxSessionGap = 4 * 60 * 60; // 4 hours

        $possibleSession = null;
        foreach ($sessions as &$session) {
            if ($session['StartTime'] <= $when) {
                if ($session['EndTime'] + $maxSessionGap > $when) {
                    $session['Achievements'][] = $createSessionAchievement($playerAchievement, $when, $hardcore);
                    if ($when > $session['EndTime']) {
                        $session['EndTime'] = $when;
                    }

                    return;
                }
                $possibleSession = $session;
            }
        }

        if ($possibleSession) {
            if ($when - $possibleSession['EndTime'] < $maxSessionGap) {
                $possibleSession['Achievements'][] = $createSessionAchievement($playerAchievement, $when, $hardcore);
                if ($when > $possibleSession['EndTime']) {
                    $possibleSession['EndTime'] = $when;
                }

                return;
            }

            $index = array_search($sessions, $possibleSession);
            if ($index < count($sessions)) {
                $possibleSession = $sessions[$index + 1];
                if ($possibleSession['StartTime'] - $when < $maxSessionGap) {
                    $possibleSession['Achievements'][] = $createSessionAchievement($playerAchievement, $when, $hardcore);
                    $possibleSession['StartTime'] = $when;

                    return;
                }
            }
        }

        $sessions[] = [
            'StartTime' => $when,
            'EndTime' => $when,
            'IsGenerated' => true,
            'Achievements' => [$createSessionAchievement($playerAchievement, $when, $hardcore)],
        ];
        usort($sessions, fn ($a, $b) => $b['StartTime'] - $a['StartTime']);
    };

    $playerAchievements = PlayerAchievement::where('player_achievements.user_id', '=', $user->ID)
        ->join('Achievements', 'player_achievements.achievement_id', '=', 'Achievements.ID')
        ->where('Achievements.GameID', '=', $gameID)
        ->orderBy('player_achievements.unlocked_at')
        ->select(['player_achievements.*', 'Achievements.Flags', 'Achievements.Title',
                  'Achievements.Description', 'Achievements.Points', 'Achievements.BadgeName'])
        ->get();
    foreach ($playerAchievements as $playerAchievement) {
        if ($playerAchievement->Flags != AchievementFlag::OfficialCore) {
            $unofficialAchievements[$playerAchievement->achievement_id] = 1;
        }

        $achievements[$playerAchievement->achievement_id] = $playerAchievement->unlocked_at->unix();

        if ($playerAchievement->unlocked_hardcore_at) {
            $addAchievementToSession($sessions, $playerAchievement, $playerAchievement->unlocked_hardcore_at->unix(), true);

            if ($playerAchievement->unlocked_hardcore_at != $playerAchievement->unlocked_at) {
                $addAchievementToSession($sessions, $playerAchievement, $playerAchievement->unlocked_at->unix(), false);
            }
        } else {
            $addAchievementToSession($sessions, $playerAchievement, $playerAchievement->unlocked_at->unix(), false);
        }
    }

    // sort everything and find the first and last achievement timestamps
    usort($sessions, fn ($a, $b) => $a['StartTime'] - $b['StartTime']);

    $hasGenerated = false;
    $totalTime = 0;
    $achievementsTime = 0;
    $intermediateTime = 0;
    $unlockSessionCount = 0;
    $intermediateSessionCount = 0;
    $firstAchievementTime = null;
    $lastAchievementTime = null;
    foreach ($sessions as &$session) {
        $elapsed = ($session['EndTime'] - $session['StartTime']);
        $totalTime += $elapsed;

        if (!empty($session['Achievements'])) {
            if ($achievementsTime > 0) {
                $achievementsTime += $intermediateTime;
                $unlockSessionCount += $intermediateSessionCount;
            }
            $achievementsTime += $elapsed;
            $intermediateTime = 0;
            $intermediateSessionCount = 0;

            $unlockSessionCount++;
            usort($session['Achievements'], fn ($a, $b) => $a['When'] - $b['When']);
            foreach ($session['Achievements'] as &$achievement) {
                if ($firstAchievementTime === null) {
                    $firstAchievementTime = $achievement['When'];
                }
                $lastAchievementTime = $achievement['When'];
            }

            if ($session['IsGenerated']) {
                $hasGenerated = true;
            }
        } else {
            $intermediateTime += $elapsed;
            $intermediateSessionCount++;
        }
    }

    // assume every achievement took roughly the same amount of time to earn. divide the
    // user's total known playtime by the number of achievements they've earned to get the
    // approximate time per achievement earned. add this value to each session to account
    // for time played after getting the last achievement of the session.
    $achievementsUnlocked = count($achievements);
    if ($hasGenerated && $achievementsUnlocked > 0) {
        $sessionAdjustment = $achievementsTime / $achievementsUnlocked;
        $totalTime += $sessionAdjustment * count($sessions);
        if ($unlockSessionCount > 1) {
            $achievementsTime += $sessionAdjustment * $unlockSessionCount;
        }
    } else {
        $sessionAdjustment = 0;
    }

    $activity = [
        'Sessions' => $sessions,
        'TotalTime' => $totalTime,
        'AchievementsTime' => $achievementsTime,
        'PerSessionAdjustment' => $sessionAdjustment,
        'AchievementsUnlocked' => count($achievements) - count($unofficialAchievements),
        'UnlockSessionCount' => $unlockSessionCount,
        'FirstUnlockTime' => $firstAchievementTime,
        'LastUnlockTime' => $lastAchievementTime,
        'TotalUnlockTime' => ($lastAchievementTime != null) ? $lastAchievementTime - $firstAchievementTime : 0,
        'CoreAchievementCount' => $game->achievements_published,
    ];

    return $activity;
}
