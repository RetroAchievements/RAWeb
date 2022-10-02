<?php

use RA\AchievementType;
use RA\ActivityType;
use RA\UnlockMode;

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
        attributeDevelopmentAuthor($achData['Author'], $pointsToGive);
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

function resetAchievements(string $user, $gameID): int
{
    sanitize_sql_inputs($user, $gameID);
    settype($gameID, 'integer');

    if (empty($gameID)) {
        return 0;
    }

    $query = "DELETE FROM Awarded WHERE User='$user' AND AchievementID IN ( SELECT ID FROM Achievements WHERE Achievements.GameID='$gameID')";

    $numRowsDeleted = 0;
    if (s_mysql_query($query) !== false) {
        $db = getMysqliConnection();
        $numRowsDeleted = (int) mysqli_affected_rows($db);
    }

    expireGameTopAchievers($gameID);

    recalculatePlayerPoints($user);
    return $numRowsDeleted;
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

    if (!$dbResult) {
        log_sql_fail();
    }

    getAchievementTitle($achID, $gameTitle, $gameID);
    expireGameTopAchievers($gameID);

    recalculatePlayerPoints($user);
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

function getAchievementUnlocksData(
    $achID,
    &$numWinners,
    &$numPossibleWinners,
    &$numRecentWinners,
    &$winnerInfo,
    $user,
    $offset = 0,
    $limit = 50
): bool {
    sanitize_sql_inputs($achID, $user, $offset, $limit);

    $winnerInfo = [];

    $query = "
        SELECT ach.GameID, COUNT(tracked_aw.AchievementID) AS NumEarned
        FROM Achievements AS ach
        LEFT JOIN (
            SELECT aw.AchievementID
            FROM Awarded AS aw
            INNER JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE aw.AchievementID = $achID AND aw.HardcoreMode = 0
              AND (NOT ua.Untracked OR ua.User = \"$user\")
        ) AS tracked_aw ON tracked_aw.AchievementID = ach.ID
        WHERE ach.ID = $achID
    ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    $numWinners = $data['NumEarned'];
    $gameID = $data['GameID'];   // Grab GameID at this point

    $numPossibleWinners = getTotalUniquePlayers($gameID, $user, false, null);

    // Get recent winners, and their most recent activity:
    $query = "SELECT ua.User, ua.RAPoints,
                     IFNULL(aw_hc.Date, aw_sc.Date) AS DateAwarded,
                     CASE WHEN aw_hc.Date IS NOT NULL THEN 1 ELSE 0 END AS HardcoreMode
              FROM UserAccounts ua
              INNER JOIN
                     (SELECT User, Date FROM Awarded WHERE AchievementID = $achID AND HardcoreMode = 0) AS aw_sc
                     ON aw_sc.User = ua.User
              LEFT JOIN
                     (SELECT User, Date FROM Awarded WHERE AchievementID = $achID AND HardcoreMode = 1) AS aw_hc
                     ON aw_hc.User = ua.User
              WHERE (NOT ua.Untracked OR ua.User = \"$user\" )
              ORDER BY DateAwarded DESC
              LIMIT $offset, $limit";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $winnerInfo[] = $db_entry;
        }
    }

    return true;
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
function getAchievementDistribution(int $gameID, int $hardcore, ?string $requestedBy, int $flags, $numAchievements = null): array
{
    sanitize_sql_inputs($gameID, $hardcore, $requestedBy, $flags);
    settype($gameID, 'integer');
    settype($hardcore, 'integer');
    settype($flags, 'integer');
    $retval = [];

    // Returns an array of the number of players who have achieved each total, up to the max.
    $query = "
        SELECT InnerTable.AwardedCount, COUNT(*) AS NumUniquePlayers
        FROM (
            SELECT COUNT(*) AS AwardedCount
            FROM Awarded AS aw
            LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
            LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
            LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
            WHERE gd.ID = $gameID AND aw.HardcoreMode = $hardcore AND ach.Flags = $flags
              AND (NOT ua.Untracked" . (isset($requestedBy) ? " OR ua.User = '$requestedBy'" : "") . ")
            GROUP BY aw.User
            ORDER BY AwardedCount DESC
        ) AS InnerTable
        GROUP BY InnerTable.AwardedCount";

    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $awardedCount = $data['AwardedCount'];
            $numUnique = $data['NumUniquePlayers'];
            settype($awardedCount, 'integer');
            settype($numUnique, 'integer');
            $retval[$awardedCount] = $numUnique;
        }

        // fill the gaps and sort
        if ($numAchievements === null) {
            $numAchievements = getGameMetadataByFlags($gameID, $requestedBy, $achievementData, $gameData, 1, null, $flags);
        }

        for ($i = 1; $i <= $numAchievements; $i++) {
            if (!array_key_exists($i, $retval)) {
                $retval[$i] = 0;
            }
        }
        ksort($retval);
    }

    return $retval;
}
