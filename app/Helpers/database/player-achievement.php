<?php

use App\Community\Enums\ActivityType;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievementLegacy;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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

function unlockAchievement(string $username, int $achievementId, bool $isHardcore): array
{
    $retVal = [
        'Success' => false,
    ];

    $user = User::firstWhere('User', $username);
    if (!$user) {
        $retVal['Error'] = "Data not found for user $username";

        return $retVal;
    }

    $achievement = Achievement::find($achievementId);
    if (!$achievement) {
        $retVal['Error'] = "Data not found for achievement $achievementId";

        return $retVal;
    }

    if ($achievement->Flags === AchievementType::Unofficial) { // do not award Unofficial achievements
        $retVal['Error'] = "Unofficial achievements cannot be unlocked";

        return $retVal;
    }

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

    // TODO dispatch user unlock event to start/extend player session, upsert user game entry

    if (!$alreadyAwarded) {
        // testFullyCompletedGame could post a mastery notification. make sure to post
        // the achievement unlock notification first
        postActivity($user, ActivityType::UnlockedAchievement, $achievement->ID, (int) $isHardcore);
    }

    $completion = testFullyCompletedGame($achievement->GameID, $user->User, $isHardcore, !$alreadyAwarded);
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

    // Use raw statement to ensure updates are atomic. Modifying the user model and
    // committing via save() leaves a window where multiple simultaneous unlocks can
    // increment the score separately and miss the merged result. For example:
    // * unlock A => read points = 10
    // * unlock B => read points = 10
    // * unlock A => award 5 points, total = 15
    // * unlock B => award 10 points, total = 20
    // * unlock A => commit points (15)
    // * unlock B => commit points (20)
    // -- actual points is 20, expected points should be 25: 10 + 5 (A) + 10 (B)
    $updateClause = '';
    if ($isHardcore) {
        $updateClause = 'RAPoints = RAPoints + ' . $achievement->Points;
        $updateClause .= ', TrueRAPoints = TrueRAPoints + ' . $achievement->TrueRatio;
        if ($hasRegular) {
            $updateClause .= ', RASoftcorePoints = RASoftcorePoints - ' . $achievement->Points;
        }
    } else {
        $updateClause = 'RASoftcorePoints = RASoftcorePoints + ' . $achievement->Points;
    }

    legacyDbStatement("UPDATE UserAccounts SET $updateClause, Updated=:now WHERE User=:user",
                      ['user' => $user->User, 'now' => Carbon::now()]);

    $retVal['Success'] = true;
    // Achievements all awarded. Now housekeeping (no error handling?)

    expireUserAchievementUnlocksForGame($user->User, $achievement->GameID);

    static_setlastearnedachievement($achievement->ID, $user->User, $achievement->Points);

    if ($user->User != $achievement->Author) {
        if ($isHardcore && $hasRegular) {
            // developer received contribution points when the regular version was unlocked
        } else {
            attributeDevelopmentAuthor($achievement->Author, 1, $achievement->Points);
        }
    }

    return $retVal;
}

function getAchievementUnlockCount(int $achID): int
{
    $query = "SELECT COUNT(*) AS NumEarned FROM Awarded
              WHERE AchievementID=$achID AND HardcoreMode=0";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    $data = mysqli_fetch_assoc($dbResult);

    return $data['NumEarned'] ?? 0;
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
    $numPossibleWinners = getTotalUniquePlayers((int) $data['GameID'], $parentGameId, requestedBy: $username, achievementType: AchievementType::OfficialCore);

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
    sanitize_sql_inputs($user);

    $retVal = [];

    // Fetch the number of times this has been earned whatsoever (excluding hardcore)
    $query = "SELECT COUNT(*) AS NumEarned, ach.GameID
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              WHERE AchievementID=$achID AND aw.HardcoreMode = " . UnlockMode::Softcore;

    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    $retVal['NumEarned'] = (int) $data['NumEarned'];
    $retVal['GameID'] = (int) $data['GameID'];

    // Fetch the total number of players for this game:
    $retVal['TotalPlayers'] = getUniquePlayersByUnlocks($retVal['GameID']);

    $extraWhere = "";
    if ($friendsOnly && isset($user) && $user) {
        $friendSubquery = GetFriendsSubquery($user, false);
        $extraWhere = " AND aw.User IN ( $friendSubquery ) ";
    }

    // Get recent winners, and their most recent activity:
    $query = "SELECT aw.User, ua.RAPoints, UNIX_TIMESTAMP(aw.Date) AS DateAwarded
              FROM Awarded AS aw
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE AchievementID=$achID AND aw.HardcoreMode = " . UnlockMode::Softcore . " $extraWhere
              ORDER BY aw.Date DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        $db_entry['RAPoints'] = (int) $db_entry['RAPoints'];
        $db_entry['DateAwarded'] = (int) $db_entry['DateAwarded'];
        $retVal['RecentWinner'][] = $db_entry;
    }

    return $retVal;
}

function getUniquePlayersByUnlocks(int $gameID): int
{
    $query = "SELECT MAX( Inner1.MaxAwarded ) AS TotalPlayers FROM
              (
                  SELECT ach.ID, COUNT(*) AS MaxAwarded
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                  WHERE gd.ID = $gameID AND aw.HardcoreMode = " . UnlockMode::Softcore . "
                  GROUP BY ach.ID
              ) AS Inner1";

    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    return (int) $data['TotalPlayers'];
}

/**
 * Gets the number of softcore and hardcore awards for an achievement since a given time.
 */
function getUnlocksSince(int $id, string $date): array
{
    sanitize_sql_inputs($date);

    $query = "
        SELECT
            COALESCE(SUM(CASE WHEN HardcoreMode = " . UnlockMode::Softcore . " THEN 1 ELSE 0 END), 0) AS softcoreCount,
            COALESCE(SUM(CASE WHEN HardcoreMode = " . UnlockMode::Hardcore . " THEN 1 ELSE 0 END), 0) AS hardcoreCount
        FROM
            Awarded
        WHERE
            AchievementID = $id
        AND
            Date > '$date'";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    return [
        'softcoreCount' => 0,
        'hardcoreCount' => 0,
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

    $dateQuery = "";
    if (strtotime($startTime)) {
        if (strtotime($endTime)) {
            // valid start and end
            $dateQuery = "AND aw.Date BETWEEN '$startTime' AND '$endTime'";
        } else {
            // valid start, invalid end
            $dateQuery = "AND aw.Date >= '$startTime'";
        }
    } else {
        if (strtotime($endTime)) {
            // invalid start, valid end
            $dateQuery = "AND aw.Date <= '$endTime'";
        } else {
            // invalid start and end
            // no date query needed
        }
    }

    $userArray = [];
    foreach ($achievementIDs as $nextID) {
        $query = "SELECT aw.User
                      FROM Awarded AS aw
                      LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                      WHERE aw.AchievementID = '$nextID'
                      AND aw.HardcoreMode = '$hardcoreMode'
                      AND ua.Untracked = 0
                      $dateQuery";
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
    int $flags = AchievementType::OfficialCore
): array {
    /** @var Game $game */
    $game = Game::withCount(['achievements' => fn ($query) => $query->type($flags)])
        ->find($gameID);

    if (!$game || !$game->achievements_count) {
        // NOTE this will return an empty array instead of an empty object. keep it like this for backwards compatibility.
        return [];
    }

    $bindings = [
        'gameId' => $gameID,
        'unlockMode' => $hardcore,
        'achievementType' => $flags,
    ];

    $requestedByStatement = '';
    if ($requestedBy) {
        $bindings['requestedBy'] = $requestedBy;
        $requestedByStatement = 'OR ua.User = :requestedBy';
    }

    // Returns an array of the number of players who have achieved each total, up to the max.
    $query = "
        SELECT InnerTable.AwardedCount, COUNT(*) AS NumUniquePlayers
        FROM (
            SELECT COUNT(*) AS AwardedCount
            FROM Awarded AS aw
            LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
            LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
            LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE gd.ID = :gameId
              AND aw.HardcoreMode = :unlockMode
              AND ach.Flags = :achievementType
              AND (NOT ua.Untracked $requestedByStatement)
            GROUP BY aw.User
            ORDER BY AwardedCount DESC
        ) AS InnerTable
        GROUP BY InnerTable.AwardedCount";

    $data = legacyDbFetchAll($query, $bindings)
        ->mapWithKeys(fn ($distribution) => [(int) $distribution['AwardedCount'] => (int) $distribution['NumUniquePlayers']]);

    return collect()->range(1, $game->achievements_count)
        ->flip()
        ->map(fn ($value, $index) => $data->get($index, 0))
        ->sortKeys()
        ->toArray();
}
