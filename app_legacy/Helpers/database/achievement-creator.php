<?php

use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Platform\Models\PlayerBadge;

/**
 * Gets the number of achievements made by the user for each console they have worked on.
 */
function getUserAchievementsPerConsole(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT COUNT(a.GameID) AS AchievementCount, c.Name AS ConsoleName
              FROM Achievements as a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = '$user'
              AND a.Flags = " . AchievementType::OfficialCore . "
              AND gd.ConsoleID NOT IN (100, 101)
              GROUP BY ConsoleName
              ORDER BY AchievementCount DESC, ConsoleName";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

/**
 * Gets the number of sets worked on by the user for each console they have worked on.
 */
function getUserSetsPerConsole(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT COUNT(DISTINCT(a.GameID)) AS SetCount, c.Name AS ConsoleName
              FROM Achievements AS a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = '$user'
              AND a.Flags = " . AchievementType::OfficialCore . "
              AND gd.ConsoleID NOT IN (100, 101)
              GROUP BY ConsoleName
              ORDER BY SetCount DESC, ConsoleName";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

/**
 * Gets information for all achievements made by the user.
 */
function getUserAchievementInformation(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT c.Name AS ConsoleName, a.ID, a.GameID, a.Title, a.Description, a.BadgeName, a.Points, a.TrueRatio, a.Author, a.DateCreated, gd.Title AS GameTitle, LENGTH(a.MemAddr) AS MemLength, ua.ContribCount, ua.ContribYield
              FROM Achievements AS a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.User = '$user'
              WHERE Author LIKE '$user'
              AND a.Flags = " . AchievementType::OfficialCore . "
              AND gd.ConsoleID NOT IN (100, 101)
              ORDER BY a.DateCreated";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

/**
 * Gets the number of time the user has obtained (softcore and hardcore) their own achievements.
 */
function getOwnAchievementsObtained(string $user): bool|array|null
{
    sanitize_sql_inputs($user);

    $query = "SELECT
              SUM(CASE WHEN aw.HardcoreMode = " . UnlockMode::Softcore . " THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN aw.HardcoreMode = " . UnlockMode::Hardcore . " THEN 1 ELSE 0 END) AS HardcoreCount
              FROM Achievements AS a
              LEFT JOIN Awarded AS aw ON aw.AchievementID = a.ID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author LIKE '$user'
              AND aw.User LIKE '$user'
              AND a.Flags = " . AchievementType::OfficialCore . "
              AND gd.ConsoleID NOT IN (100, 101)";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    } else {
        return null;
    }
}

/**
 * Gets data for other users that have earned achievements for the input user.
 */
function getObtainersOfSpecificUser(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT aw.User, COUNT(aw.User) AS ObtainCount,
              SUM(CASE WHEN aw.HardcoreMode = " . UnlockMode::Softcore . " THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN aw.HardcoreMode = " . UnlockMode::Hardcore . " THEN 1 ELSE 0 END) AS HardcoreCount
              FROM Achievements AS a
              LEFT JOIN Awarded AS aw ON aw.AchievementID = a.ID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE a.Author LIKE '$user'
              AND aw.User NOT LIKE '$user'
              AND a.Flags = " . AchievementType::OfficialCore . "
              AND gd.ConsoleID NOT IN (100, 101)
              AND Untracked = 0
              GROUP BY aw.User
              ORDER BY ObtainCount DESC";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}

/**
 * Checks to see if a user is the sole author of a set.
 */
function checkIfSoleDeveloper(string $user, int $gameID): bool
{
    sanitize_sql_inputs($user, $gameID);
    settype($gameID, 'integer');

    $query = "
        SELECT distinct(Author) AS Author FROM Achievements AS ach
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        WHERE ach.GameID = $gameID
        AND ach.Flags = " . AchievementType::OfficialCore . "";

    $dbResult = s_mysql_query($query);

    $userFound = false;
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            if ($user != $data['Author']) {
                return false;
            } else {
                $userFound = true;
            }
        }
    }

    return $userFound;
}

function attributeDevelopmentAuthor(string $author, int $count, int $points): void
{
    sanitize_sql_inputs($author);

    $query = "SELECT ContribCount, ContribYield FROM UserAccounts WHERE User = '$author'";
    $dbResult = s_mysql_query($query);
    $oldResults = mysqli_fetch_assoc($dbResult);
    if (!$oldResults) {
        // could not find a record for the author, nothing to update
        return;
    }

    $oldContribCount = (int) $oldResults['ContribCount'];
    $oldContribYield = (int) $oldResults['ContribYield'];

    // Update the fact that this author made an achievement that just got earned.
    $query = "UPDATE UserAccounts AS ua
              SET ua.ContribCount = ua.ContribCount+$count, ua.ContribYield = ua.ContribYield+$points
              WHERE ua.User = '$author'";

    $dbResult = s_mysql_query($query);

    if (!$dbResult) {
        log_sql_fail();

        return;
    }

    $newContribTier = PlayerBadge::getNewBadgeTier(AwardType::AchievementUnlocksYield, $oldContribCount, $oldContribCount + $count);
    if ($newContribTier !== null) {
        AddSiteAward($author, AwardType::AchievementUnlocksYield, $newContribTier);
    }

    $newPointsTier = PlayerBadge::getNewBadgeTier(AwardType::AchievementPointsYield, $oldContribYield, $oldContribYield + $points);
    if ($newPointsTier !== null) {
        AddSiteAward($author, AwardType::AchievementPointsYield, $newPointsTier);
    }
}

function recalculateDeveloperContribution(string $author): void
{
    sanitize_sql_inputs($author);

    $query = "SELECT COUNT(*) AS ContribCount, SUM(Points) AS ContribYield
              FROM (SELECT aw.User, ach.ID, MAX(aw.HardcoreMode) as HardcoreMode, ach.Points
                    FROM Achievements ach LEFT JOIN Awarded aw ON aw.AchievementID=ach.ID
                    WHERE ach.Author='$author' AND aw.User != '$author'
                    AND ach.Flags=" . AchievementType::OfficialCore . "
                    GROUP BY 1,2) AS UniqueUnlocks";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $contribCount = 0;
        $contribYield = 0;

        if ($data = mysqli_fetch_assoc($dbResult)) {
            $contribCount = $data['ContribCount'] ?? 0;
            $contribYield = $data['ContribYield'] ?? 0;
        }

        $query = "UPDATE UserAccounts
                  SET ContribCount = $contribCount, ContribYield = $contribYield
                  WHERE User = '$author'";

        $dbResult = s_mysql_query($query);
        if (!$dbResult) {
            log_sql_fail();
        }
    }
}
