<?php

use Illuminate\Support\Collection;
use LegacyApp\Community\Enums\ActivityType;
use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Platform\Models\Game;

function playerHasUnlock(?string $user, $achievementId): array
{
    sanitize_sql_inputs($user, $achievementId);

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
              WHERE AchievementID = '$achievementId' AND User = '$user'";

    $dbResult = s_mysql_query($query);
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
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

function unlockAchievement(string $user, $achIDToAward, $isHardcore): array
{
    sanitize_sql_inputs($user, $achIDToAward, $isHardcore);
    settype($achIDToAward, 'integer');
    settype($isHardcore, 'integer');

    $retVal = [
        'Success' => false,
    ];

    if ($achIDToAward <= 0) {
        $retVal['Error'] = "Achievement ID <= 0! Cannot unlock";

        return $retVal;
    }

    if (!isValidUsername($user)) {
        $retVal['Error'] = "User is '$user', cannot unlock achievement";

        return $retVal;
    }

    $userData = GetUserData($user);
    if (!$userData) {
        $retVal['Error'] = "User data cannot be found for $user";

        return $retVal;
    }

    $achData = GetAchievementMetadataJSON($achIDToAward);
    if (!$achData) {
        $retVal['Error'] = "Achievement data cannot be found for $achIDToAward";

        return $retVal;
    }

    if ((int) $achData['Flags'] === AchievementType::Unofficial) { // do not award Unofficial achievements
        $retVal['Error'] = "Unofficial achievements cannot be unlocked";

        return $retVal;
    }

    $hasAwardTypes = playerHasUnlock($user, $achIDToAward);
    $hasRegular = $hasAwardTypes['HasRegular'];
    $hasHardcore = $hasAwardTypes['HasHardcore'];
    $alreadyAwarded = $isHardcore ? $hasHardcore : $hasRegular;

    $awardedOK = true;
    if ($isHardcore && !$hasHardcore) {
        $awardedOK &= insertAchievementUnlockIntoAwardedTable($user, $achIDToAward, true);
    }
    if (!$hasRegular && $awardedOK) {
        $awardedOK &= insertAchievementUnlockIntoAwardedTable($user, $achIDToAward, false);
    }

    if (!$awardedOK) {
        $retVal['Error'] = "Could not unlock achievement for player";

        return $retVal;
    }

    if (!$alreadyAwarded) {
        // testFullyCompletedGame could post a mastery notification. make sure to post
        // the achievement unlock notification first
        postActivity($user, ActivityType::EarnedAchievement, $achIDToAward, $isHardcore);
    }

    $completion = testFullyCompletedGame($achData['GameID'], $user, $isHardcore, !$alreadyAwarded);
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

    $pointsToGive = $achData['Points'];
    $trueRatio = $achData['TrueRatio'];
    settype($pointsToGive, 'integer');
    settype($trueRatio, 'integer');

    if ($isHardcore) {
        if ($hasRegular) {
            $setPointsString = "SET RAPoints=RAPoints+$pointsToGive, TrueRAPoints=TrueRAPoints+$trueRatio, RASoftcorePoints=RASoftcorePoints-$pointsToGive, Updated=NOW()";
        } else {
            $setPointsString = "SET RAPoints=RAPoints+$pointsToGive, TrueRAPoints=TrueRAPoints+$trueRatio, Updated=NOW()";
        }
    } else {
        $setPointsString = "SET RASoftcorePoints=RASoftcorePoints+$pointsToGive, Updated=NOW()";
    }

    $query = "UPDATE UserAccounts $setPointsString WHERE User='$user'";
    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        $retVal['Error'] = "Could not add points for this player";

        return $retVal;
    }

    $retVal['Success'] = true;
    // Achievements all awarded. Now housekeeping (no error handling?)

    static_setlastearnedachievement($achIDToAward, $user, $achData['Points']);

    if ($user != $achData['Author']) {
        if ($isHardcore && $hasRegular) {
            // developer received contribution points when the regular version was unlocked
        } else {
            attributeDevelopmentAuthor($achData['Author'], 1, $pointsToGive);
        }
    }

    return $retVal;
}

function insertAchievementUnlockIntoAwardedTable(string $user, $achIDToAward, $isHardcore): bool
{
    sanitize_sql_inputs($user, $achIDToAward, $isHardcore);
    settype($isHardcore, 'integer');

    $query = "INSERT INTO Awarded ( User, AchievementID, Date, HardcoreMode )
              VALUES ( '$user', '$achIDToAward', NOW(), '$isHardcore' )
              ON DUPLICATE KEY
              UPDATE User=User, AchievementID=AchievementID, Date=Date, HardcoreMode=HardcoreMode";

    $dbResult = s_mysql_query($query);

    return $dbResult !== false;
}

function resetAchievements(string $user, int $gameID): int
{
    sanitize_sql_inputs($user);

    getUserUnlocksDetailed($user, $gameID, $dataOut);
    $resetCount = collect($dataOut)->unique('ID')->count();
    if ($resetCount == 0) {
        return 0;
    }

    $achievementIDs = collect($dataOut)->unique('ID')->implode('ID', ',');

    // delete the unlocks for the user
    $query = "DELETE FROM Awarded WHERE User='$user' AND AchievementID IN ($achievementIDs)";
    if (!s_mysql_query($query)) {
        log_sql_fail();

        return 0;
    }

    // delete any site awards for the user
    $query = "DELETE FROM SiteAwards WHERE User = '$user' AND AwardType = " . AwardType::Mastery . " AND AwardData = $gameID";
    s_mysql_query($query);

    // force the top achievers for the game to be recalculated
    expireGameTopAchievers($gameID);

    // update the player's points
    recalculatePlayerPoints($user);

    // update the developer's contributions
    $query = "SELECT Author, COUNT(*) AS Count, SUM(Points) AS Points
              FROM Achievements WHERE ID IN ($achievementIDs)
              AND Flags=" . AchievementType::OfficialCore . " GROUP BY 1";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if ($data['Count'] > 0) {
                attributeDevelopmentAuthor($data['Author'], -(int) $data['Count'], -(int) $data['Points']);
            }
        }
    }

    return $resetCount;
}

function resetSingleAchievement(string $user, $achID): bool
{
    sanitize_sql_inputs($user, $achID);
    settype($achID, 'integer');

    if (empty($achID)) {
        return false;
    }

    $query = "DELETE FROM Awarded WHERE User='$user' AND AchievementID='$achID'";
    $dbResult = s_mysql_query($query);

    $numRowsDeleted = 0;
    if (!$dbResult) {
        log_sql_fail();
    } else {
        $db = getMysqliConnection();
        $numRowsDeleted = (int) mysqli_affected_rows($db);
    }

    if ($numRowsDeleted > 0) {
        $achData = Achievement::find($achID);
        if ($achData['Flags'] == AchievementType::OfficialCore) {
            // user no longer has all core achievements, delete their site award
            // (does nothing if they don't have a site award)
            $query = "DELETE FROM SiteAwards WHERE User = '$user' AND AwardType = " . AwardType::Mastery . " AND AwardData = " . $achData['GameID'];
            s_mysql_query($query);

            // force the top achievers for the game to be recalculated
            expireGameTopAchievers($achData['GameID']);

            // update the developer's contributions
            attributeDevelopmentAuthor($achData['Author'], -1, -$achData['Points']);

            // update the player's points
            recalculatePlayerPoints($user);
        }
    }

    return true;
}

function getUsersRecentAwardedForGames(string $user, $gameIDsCSV, $numAchievements, &$dataOut): void
{
    sanitize_sql_inputs($user, $numAchievements);
    settype($numAchievements, 'integer');

    if (empty($gameIDsCSV)) {
        return;
    }

    $gameIDsArray = explode(',', $gameIDsCSV);

    $gameIDs = [];
    foreach ($gameIDsArray as $gameID) {
        settype($gameID, "integer");
        $gameIDs[] = $gameID;
    }
    $gameIDs = implode(',', $gameIDs);

    $limit = ($numAchievements == 0) ? 5000 : $numAchievements;

    // TODO: because of the "ORDER BY HardcoreAchieved", this query only returns non-hardcore
    //       unlocks if the user has more than $limit unlocks. Note that $limit appears to be
    //       default (5000) for all use cases except API_GetUserSummary
    $query = "SELECT ach.ID, ach.GameID, gd.Title AS GameTitle, ach.Title, ach.Description, ach.Points, ach.BadgeName, (!ISNULL(aw.User)) AS IsAwarded, aw.Date AS DateAwarded, (aw.HardcoreMode) AS HardcoreAchieved
              FROM Achievements AS ach
              LEFT OUTER JOIN Awarded AS aw ON aw.User = '$user' AND aw.AchievementID = ach.ID
              LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
              WHERE ach.Flags = " . AchievementType::OfficialCore . "
              AND ach.GameID IN ( $gameIDs )
              ORDER BY IsAwarded DESC, HardcoreAchieved, DateAwarded DESC, ach.DisplayOrder, ach.ID
              LIMIT $limit";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $dataOut[$db_entry['GameID']][$db_entry['ID']] = $db_entry;
        }
    }
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

function getAchievementUnlocksData(
    $achievementId,
    &$numWinners,
    &$numPossibleWinners,
    $username,
    $offset = 0,
    $limit = 50
): Collection {
    $query = "
        SELECT ach.GameID, COUNT(tracked_aw.AchievementID) AS NumEarned
        FROM Achievements AS ach
        LEFT JOIN (
            SELECT aw.AchievementID
            FROM Awarded AS aw
            INNER JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE aw.AchievementID = :joinAchievementId AND aw.HardcoreMode = :unlockMode
              AND (NOT ua.Untracked OR ua.User = :username)
        ) AS tracked_aw ON tracked_aw.AchievementID = ach.ID
        WHERE ach.ID = :achievementId
    ";

    $data = legacyDbFetch($query, [
        'unlockMode' => UnlockMode::Softcore,
        'joinAchievementId' => $achievementId,
        'username' => $username,
        'achievementId' => $achievementId,
    ]);

    $numWinners = $data['NumEarned'];
    $numPossibleWinners = getTotalUniquePlayers($data['GameID'], $username);

    // Get recent winners, and their most recent activity:
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
              WHERE (NOT ua.Untracked OR ua.User = :username)
              ORDER BY DateAwarded DESC
              LIMIT :offset, :limit";

    return legacyDbFetchAll($query, [
        'joinSoftcoreAchievementId' => $achievementId,
        'joinHardcoreAchievementId' => $achievementId,
        'username' => $username,
        'offset' => $offset,
        'limit' => $limit,
    ]);
}

function getRecentUnlocksPlayersData($achID, $offset, $count, ?string $user = null, $friendsOnly = null): array
{
    sanitize_sql_inputs($achID, $offset, $count, $user, $friendsOnly);

    $retVal = [];

    // Fetch the number of times this has been earned whatsoever (excluding hardcore)
    $query = "SELECT COUNT(*) AS NumEarned, ach.GameID
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              WHERE AchievementID=$achID AND aw.HardcoreMode = " . UnlockMode::Softcore;

    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    $retVal['NumEarned'] = $data['NumEarned'];
    settype($retVal['NumEarned'], 'integer');
    $retVal['GameID'] = $data['GameID'];
    settype($retVal['GameID'], 'integer');

    // Fetch the total number of players for this game:
    $retVal['TotalPlayers'] = getUniquePlayersByUnlocks($retVal['GameID']);

    $extraWhere = "";
    if (isset($friendsOnly) && $friendsOnly && isset($user) && $user) {
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
        // settype( $db_entry['HardcoreMode'], 'integer' );
        settype($db_entry['RAPoints'], 'integer');
        settype($db_entry['DateAwarded'], 'integer');
        $retVal['RecentWinner'][] = $db_entry;
    }

    return $retVal;
}

function getUniquePlayersByUnlocks($gameID): int
{
    sanitize_sql_inputs($gameID);

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
    sanitize_sql_inputs($id, $date);
    settype($id, "integer");
    settype($date, "string");

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
    } else {
        return [
            'softcoreCount' => 0,
            'hardcoreCount' => 0,
        ];
    }
}

/**
 * Get recent unlocks of a set of achievements
 */
function getRecentUnlocks(array $achievementIDs, int $offset = 0, int $count = 200): array
{
    $achievementIDs = implode(",", $achievementIDs);
    sanitize_sql_inputs($achievementIDs, $offset, $count);

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
function getUnlocksInDateRange($achievementIDs, $startTime, $endTime, $hardcoreMode): array
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
function getAchievementDistribution(int $gameID, int $hardcore, ?string $requestedBy = null, int $flags = AchievementType::OfficialCore): array
{
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

    $requestedByStatement = "";
    if ($requestedBy) {
        $bindings['requestedBy'] = $requestedBy;
        $requestedByStatement = "OR ua.User = :requestedBy";
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
