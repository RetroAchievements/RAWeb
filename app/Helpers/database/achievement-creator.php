<?php

use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;

/**
 * Gets the number of achievements made by the user for each console they have worked on.
 */
function getUserAchievementsPerConsole(string $username): array
{
    $query = "SELECT COUNT(a.GameID) AS AchievementCount, c.Name AS ConsoleName
              FROM Achievements as a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = :author
              AND a.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)
              GROUP BY ConsoleName
              ORDER BY AchievementCount DESC, ConsoleName";

    return legacyDbFetchAll($query, [
        'author' => $username,
        'achievementFlag' => AchievementFlag::OfficialCore,
    ])->toArray();
}

/**
 * Gets the number of sets worked on by the user for each console they have worked on.
 */
function getUserSetsPerConsole(string $username): array
{
    $query = "SELECT COUNT(DISTINCT(a.GameID)) AS SetCount, c.Name AS ConsoleName
              FROM Achievements AS a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author = :author
              AND a.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)
              GROUP BY ConsoleName
              ORDER BY SetCount DESC, ConsoleName";

    return legacyDbFetchAll($query, [
        'author' => $username,
        'achievementFlag' => AchievementFlag::OfficialCore,
    ])->toArray();
}

/**
 * Gets information for all achievements made by the user.
 */
function getUserAchievementInformation(string $username): array
{
    $query = "SELECT c.Name AS ConsoleName, a.ID, a.GameID, a.Title, a.Description, a.BadgeName, a.Points, a.TrueRatio, a.Author, a.DateCreated, gd.Title AS GameTitle, LENGTH(a.MemAddr) AS MemLength, ua.ContribCount, ua.ContribYield
              FROM Achievements AS a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.User = :joinUsername
              WHERE Author LIKE :author
              AND a.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)
              ORDER BY a.DateCreated";

    return legacyDbFetchAll($query, [
        'author' => $username,
        'joinUsername' => $username,
        'achievementFlag' => AchievementFlag::OfficialCore,
    ])->toArray();
}

/**
 * Gets the number of time the user has obtained (softcore and hardcore) their own achievements.
 */
function getOwnAchievementsObtained(string $username): array
{
    $query = "SELECT
              SUM(CASE WHEN aw.HardcoreMode = :sumUnlockModeSoftcore THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN aw.HardcoreMode = :sumUnlockModeHardcore THEN 1 ELSE 0 END) AS HardcoreCount
              FROM Achievements AS a
              LEFT JOIN Awarded AS aw ON aw.AchievementID = a.ID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.Author LIKE :author
              AND aw.User LIKE :username
              AND a.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)";

    return legacyDbFetch($query, [
        'author' => $username,
        'username' => $username,
        'achievementFlag' => AchievementFlag::OfficialCore,
        'sumUnlockModeSoftcore' => UnlockMode::Softcore,
        'sumUnlockModeHardcore' => UnlockMode::Hardcore,
    ]);
}

/**
 * Gets data for other users that have earned achievements for the input user.
 */
function getObtainersOfSpecificUser(string $username): array
{
    $query = "SELECT aw.User, COUNT(aw.User) AS ObtainCount,
              SUM(CASE WHEN aw.HardcoreMode = :sumUnlockModeSoftcore THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN aw.HardcoreMode = :sumUnlockModeHardcore THEN 1 ELSE 0 END) AS HardcoreCount
              FROM Achievements AS a
              LEFT JOIN Awarded AS aw ON aw.AchievementID = a.ID
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE a.Author LIKE :author
              AND aw.User NOT LIKE :username
              AND a.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)
              AND Untracked = 0
              GROUP BY aw.User
              ORDER BY ObtainCount DESC";

    return legacyDbFetchAll($query, [
        'author' => $username,
        'username' => $username,
        'achievementFlag' => AchievementFlag::OfficialCore,
        'sumUnlockModeSoftcore' => UnlockMode::Softcore,
        'sumUnlockModeHardcore' => UnlockMode::Hardcore,
    ])->toArray();
}

/**
 * Checks to see if a user is the sole author of a set.
 */
function checkIfSoleDeveloper(string $user, int $gameID): bool
{
    $query = "
        SELECT distinct(Author) AS Author FROM Achievements AS ach
        LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
        WHERE ach.GameID = :gameId
        AND ach.Flags = :achievementFlag";

    $authors = legacyDbFetchAll($query, [
        'gameId' => $gameID,
        'achievementFlag' => AchievementFlag::OfficialCore,
    ]);

    if ($authors->count() !== 1) {
        return false;
    }

    return $authors->first()['Author'] === $user;
}
