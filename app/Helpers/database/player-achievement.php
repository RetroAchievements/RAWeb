<?php

use App\Community\Enums\ActivityType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @deprecated see PlayerAchievement model
 */
function playerHasUnlock(?string $user, int $achievementId): array
{
    $retVal = [
        'HasRegular' => false,
        'HasHardcore' => false,
        'RegularDate' => null,
        'HardcoreDate' => null,
    ];

    if (empty($user)) {
        return $retVal;
    }

    $query = "SELECT HardcoreMode, Date
              FROM Awarded
              WHERE AchievementID = '$achievementId' AND User = :user";

    foreach (legacyDbFetchAll($query, ['user' => $user]) as $nextData) {
        if ($nextData['HardcoreMode'] == 0) {
            $retVal['HasRegular'] = true;
            $retVal['RegularDate'] = $nextData['Date'];
        } elseif ($nextData['HardcoreMode'] == 1) {
            $retVal['HasHardcore'] = true;
            $retVal['HardcoreDate'] = $nextData['Date'];
        }
    }

    return $retVal;
}

/**
 * @deprecated see UnlockPlayerAchievementAction
 */
function unlockAchievement(User $user, int $achievementId, bool $isHardcore): array
{
    $retVal = [
        'Success' => false,
    ];

    $achievement = Achievement::find($achievementId);
    if (!$achievement) {
        $retVal['Error'] = "Data not found for achievement $achievementId";

        return $retVal;
    }

    if ($achievement->Flags === AchievementFlag::Unofficial) { // do not award Unofficial achievements
        $retVal['Error'] = "Unofficial achievements cannot be unlocked";

        return $retVal;
    }

    // TODO config('feature.aggregate_queries')
    $hasAwardTypes = playerHasUnlock($user->User, $achievement->ID);
    $hasRegular = $hasAwardTypes['HasRegular'];
    $hasHardcore = $hasAwardTypes['HasHardcore'];
    $alreadyAwarded = $isHardcore ? $hasHardcore : $hasRegular;

    $now = Carbon::now();
    if ($isHardcore && !$hasHardcore) {
        PlayerAchievementLegacy::firstOrCreate([
            'User' => $user->User,
            'AchievementID' => $achievement->ID,
            'HardcoreMode' => UnlockMode::Hardcore,
            'Date' => $now,
        ]);
    }
    if (!$hasRegular) {
        PlayerAchievementLegacy::firstOrCreate([
            'User' => $user->User,
            'AchievementID' => $achievement->ID,
            'HardcoreMode' => UnlockMode::Softcore,
            'Date' => $now,
        ]);
    }

    if (!$alreadyAwarded) {
        postActivity($user, ActivityType::UnlockedAchievement, $achievement->ID, (int) $isHardcore);
    }

    $completion = getUnlockCounts($achievement->GameID, $user->username, $isHardcore);
    if (array_key_exists('NumAwarded', $completion)) {
        $retVal['AchievementsRemaining'] = $completion['NumAch'] - $completion['NumAwarded'];
    }

    if ($alreadyAwarded) {
        // =============================================================================
        // ===== DO NOT CHANGE THESE MESSAGES ==========================================
        // The client detects the "User already has" and does not report them as errors.
        if ($isHardcore) {
            $retVal['Error'] = "User already has this achievement unlocked in hardcore mode.";
        } else {
            $retVal['Error'] = "User already has this achievement unlocked.";
        }
        // =============================================================================

        return $retVal;
    }

    // Optimistic update for async metrics updates
    if (config('queue.default') !== 'sync') {
        $retVal['SoftcoreScore'] = $isHardcore ? $user->points_softcore : $user->points_softcore + $achievement->points;
        $retVal['Score'] = $isHardcore ? $user->points + $achievement->points : $user->points;
    }

    $retVal['Success'] = true;
    // Achievements all awarded. Now housekeeping (no error handling?)

    expireUserCompletedGamesCacheValue($user->User);
    expireUserAchievementUnlocksForGame($user->User, $achievement->GameID);

    static_setlastearnedachievement($achievement->ID, $user->User, $achievement->Points);

    return $retVal;
}

/**
 * @deprecated use Achievements.unlocks_total
 */
function getAchievementUnlockCount(int $achID): int
{
    return PlayerAchievement::where('achievement_id', $achID)
        ->count();
}

/**
 * @return Collection<int, array>
 */
function getAchievementUnlocksData(
    int $achievementId,
    ?string $username,
    ?int &$numWinners,
    ?int &$numPossibleWinners,
    ?int $parentGameId = null,
    int $offset = 0,
    int $limit = 50
): Collection {

    $bindings = [
        'unlockMode' => UnlockMode::Softcore,
        'joinAchievementId' => $achievementId,
        'achievementId' => $achievementId,
    ];

    $requestedByStatement = '';
    if ($username) {
        $bindings['username'] = $username;
        $requestedByStatement = 'OR ua.User = :username';
    }

    $query = "
        SELECT ach.GameID, COUNT(tracked_aw.AchievementID) AS NumEarned
        FROM Achievements AS ach
        LEFT JOIN (
            SELECT aw.AchievementID
            FROM Awarded AS aw
            INNER JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE aw.AchievementID = :joinAchievementId AND aw.HardcoreMode = :unlockMode
              AND (NOT ua.Untracked $requestedByStatement)
        ) AS tracked_aw ON tracked_aw.AchievementID = ach.ID
        WHERE ach.ID = :achievementId
    ";

    $data = legacyDbFetch($query, $bindings);

    $numWinners = $data['NumEarned'];
    // TODO use $game->players_total
    $numPossibleWinners = getTotalUniquePlayers((int) $data['GameID'], $parentGameId, requestedBy: $username);

    // Get recent winners, and their most recent activity
    $bindings = [
        'joinSoftcoreAchievementId' => $achievementId,
        'joinHardcoreAchievementId' => $achievementId,
        'offset' => $offset,
        'limit' => $limit,
    ];

    $requestedByStatement = '';
    if ($username) {
        $bindings['username'] = $username;
        $requestedByStatement = 'OR ua.User = :username';
    }

    $query = "SELECT ua.User, ua.RAPoints,
                     IFNULL(aw_hc.Date, aw_sc.Date) AS DateAwarded,
                     CASE WHEN aw_hc.Date IS NOT NULL THEN 1 ELSE 0 END AS HardcoreMode
              FROM UserAccounts ua
              INNER JOIN
                     (SELECT User, Date FROM Awarded WHERE AchievementID = :joinSoftcoreAchievementId AND HardcoreMode = 0) AS aw_sc
                     ON aw_sc.User = ua.User
              LEFT JOIN
                     (SELECT User, Date FROM Awarded WHERE AchievementID = :joinHardcoreAchievementId AND HardcoreMode = 1) AS aw_hc
                     ON aw_hc.User = ua.User
              WHERE (NOT ua.Untracked $requestedByStatement)
              ORDER BY DateAwarded DESC
              LIMIT :offset, :limit";

    return legacyDbFetchAll($query, $bindings);
}

function getRecentUnlocksPlayersData(
    int $achID,
    int $offset,
    int $count,
    ?string $user = null,
    bool $friendsOnly = false
): array {
    $retVal = [
        'NumEarned' => 0,
        'GameID' => 0,
        'TotalPlayers' => 0,
        'RecentWinner' => [],
    ];

    $achievement = Achievement::find($achID);
    if (!$achievement) {
        return $retVal;
    }

    $game = Game::find($achievement->GameID);
    if (!$game) {
        return $retVal;
    }
    $retVal['GameID'] = $game->ID;

    // Fetch the number of times this has been earned whatsoever (excluding hardcore)
    $query = "SELECT COUNT(*) AS NumEarned FROM Awarded
              WHERE AchievementID=$achID AND HardcoreMode = " . UnlockMode::Softcore;
    $data = legacyDbFetch($query);
    $retVal['NumEarned'] = (int) $data['NumEarned'];

    // Fetch the total number of players for this game:
    $parentGameID = getParentGameIdFromGameTitle($game->Title, $game->ConsoleID);
    // TODO use $game->players_total
    $retVal['TotalPlayers'] = getTotalUniquePlayers($game->ID, $parentGameID);

    $extraWhere = "";
    if ($friendsOnly && isset($user) && $user) {
        $friendSubquery = GetFriendsSubquery($user, false);
        $extraWhere = " AND aw.User IN ( $friendSubquery ) ";
    }

    // Get recent winners, and their most recent activity:
    $query = "SELECT aw.User, ua.RAPoints, " . unixTimestampStatement('aw.Date', 'DateAwarded') . "
              FROM Awarded AS aw
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE AchievementID=$achID AND aw.HardcoreMode = " . UnlockMode::Softcore . " $extraWhere
              ORDER BY aw.Date DESC
              LIMIT $offset, $count";

    foreach (legacyDbFetchAll($query) as $db_entry) {
        $db_entry['RAPoints'] = (int) $db_entry['RAPoints'];
        $db_entry['DateAwarded'] = (int) $db_entry['DateAwarded'];
        $retVal['RecentWinner'][] = $db_entry;
    }

    return $retVal;
}

/**
 * Gets the number of softcore and hardcore awards for an achievement since a given time.
 */
function getUnlocksSince(int $id, string $date): array
{
    $softcoreCount = PlayerAchievement::where('achievement_id', $id)
        ->where('unlocked_at', '>', $date)->count();
    $hardcoreCount = PlayerAchievement::where('achievement_id', $id)
        ->where('unlocked_hardcore_at', '>', $date)->count();

    return [
        'softcoreCount' => $softcoreCount,
        'hardcoreCount' => $hardcoreCount,
    ];
}

/**
 * Get recent unlocks of a set of achievements
 */
function getRecentUnlocks(array $achievementIDs, int $offset = 0, int $count = 200): array
{
    $achievementIDs = implode(",", $achievementIDs);
    sanitize_sql_inputs($achievementIDs);

    $retVal = [];
    $query = "SELECT aw.User, c.Name AS ConsoleName, aw.Date, aw.AchievementID, a.GameID, aw.HardcoreMode, a.Title, a.Description, a.BadgeName, a.Points, a.TrueRatio, gd.Title AS GameTitle, gd.ImageIcon as GameIcon
              FROM Awarded AS aw
              LEFT JOIN Achievements as a ON a.ID = aw.AchievementID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE aw.AchievementID IN (" . $achievementIDs . ")
              AND gd.ConsoleID NOT IN (100, 101)
              ORDER BY aw.Date DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

/**
 * Gets a list of users who have unlocked an achievement or list of achievements within a given time-range.
 */
function getUnlocksInDateRange(array $achievementIDs, string $startTime, string $endTime, int $hardcoreMode): array
{
    if (empty($achievementIDs)) {
        return [];
    }

    $column = $hardcoreMode ? 'unlocked_hardcore_at' : 'unlocked_at';

    $dateQuery = "";
    if (strtotime($startTime)) {
        if (strtotime($endTime)) {
            // valid start and end
            $dateQuery = "AND pa.$column BETWEEN '$startTime' AND '$endTime'";
        } else {
            // valid start, invalid end
            $dateQuery = "AND pa.$column >= '$startTime'";
        }
    } else {
        if (strtotime($endTime)) {
            // invalid start, valid end
            $dateQuery = "AND pa.$column <= '$endTime'";
        } else {
            // invalid start and end
            // no date query needed
        }
    }

    $userArray = [];
    foreach ($achievementIDs as $nextID) {
        $query = "SELECT ua.User
                      FROM player_achievements AS pa
                      INNER JOIN UserAccounts AS ua ON ua.ID = pa.user_id
                      WHERE pa.achievement_id = $nextID
                      AND ua.Untracked = 0
                      $dateQuery
                      ORDER BY ua.User";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $userArray[$nextID][] = $db_entry['User'];
            }
        }
    }

    return $userArray;
}

/**
 * Gets the achievement distribution to display on the game page.
 */
function getAchievementDistribution(
    int $gameID,
    int $hardcore,
    ?string $requestedBy = null,
    int $flag = AchievementFlag::OfficialCore,
    int $numPlayers = 0
): array {
    /** @var Game $game */
    $game = Game::withCount(['achievements' => fn ($query) => $query->flag($flag)])
        ->find($gameID);

    if (!$game || !$game->achievements_count) {
        // NOTE this will return an empty array instead of an empty object. keep it like this for backwards compatibility.
        return [];
    }

    $bindings = [
        'gameId' => $gameID,
        'unlockMode' => $hardcore,
        'achievementFlag' => $flag,
    ];

    // if a game has more than 100 players, don't filter out the untracked users as the
    // join becomes very expensive. will be addressed when denormalized data is captured
    $joinStatement = '';
    $joinStatementNew = '';
    $joinStatementNewUnofficial = '';
    $requestedByStatement = '';
    if ($numPlayers < 100) {
        $joinStatement = 'LEFT JOIN UserAccounts AS ua ON ua.User = aw.User';
        $joinStatementNew = 'LEFT JOIN UserAccounts AS ua ON ua.ID = pg.user_id';
        $joinStatementNewUnofficial = 'LEFT JOIN UserAccounts AS ua ON ua.ID = pa.user_id';
        $requestedByStatement = 'AND (NOT ua.Untracked';
        if ($requestedBy) {
            $bindings['requestedBy'] = $requestedBy;
            $requestedByStatement .= ' OR ua.User = :requestedBy';
        }
        $requestedByStatement .= ')';
    }

    // Returns an array of the number of players who have achieved each total, up to the max.
    if (config('feature.aggregate_queries')) {
        if ($flag === AchievementFlag::OfficialCore) {
            $countColumn = $hardcore ? 'achievements_unlocked_hardcore' : 'achievements_unlocked';
            $query = "SELECT pg.$countColumn AS AwardedCount, COUNT(*) AS NumUniquePlayers
                      FROM player_games AS pg
                      $joinStatementNew
                      WHERE pg.game_id = :gameId AND pg.$countColumn > 0
                      $requestedByStatement
                      GROUP BY AwardedCount
                      ORDER BY AwardedCount DESC";
            unset($bindings['achievementFlag']);
        } else {
            $hardcoreStatement = $hardcore ? 'AND pa.unlocked_hardcore_at IS NOT NULL' : '';
            $query = "SELECT InnerTable.AwardedCount AS AwardedCount, COUNT(*) AS NumUniquePlayers
                    FROM (
                        SELECT COUNT(*) AS AwardedCount
                        FROM player_achievements AS pa
                        LEFT JOIN Achievements AS ach ON ach.ID = pa.achievement_id
                        $joinStatementNewUnofficial
                        WHERE ach.GameID = :gameId
                        $hardcoreStatement
                        AND ach.Flags = :achievementFlag
                        $requestedByStatement
                        GROUP BY pa.user_id
                        ORDER BY AwardedCount DESC
                    ) AS InnerTable
                    GROUP BY InnerTable.AwardedCount";
        }
        unset($bindings['unlockMode']);
    } else {
        $query = "
        SELECT InnerTable.AwardedCount, COUNT(*) AS NumUniquePlayers
        FROM (
            SELECT COUNT(*) AS AwardedCount
            FROM Awarded AS aw
            LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
            $joinStatement
            WHERE ach.GameID = :gameId
              AND aw.HardcoreMode = :unlockMode
              AND ach.Flags = :achievementFlag
              $requestedByStatement
            GROUP BY aw.User
            ORDER BY AwardedCount DESC
        ) AS InnerTable
        GROUP BY InnerTable.AwardedCount";
    }

    $data = legacyDbFetchAll($query, $bindings)
        ->mapWithKeys(fn ($distribution) => [(int) $distribution['AwardedCount'] => (int) $distribution['NumUniquePlayers']]);

    return collect()->range(1, $game->achievements_count)
        ->flip()
        ->map(fn ($value, $index) => $data->get($index, 0))
        ->sortKeys()
        ->toArray();
}
