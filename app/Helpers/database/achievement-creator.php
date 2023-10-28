<?php

use App\Platform\Enums\AchievementFlag;
use App\Site\Models\User;

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
function getOwnAchievementsObtained(User $user): array
{
    $query = "SELECT
              SUM(CASE WHEN pa.unlocked_hardcore_at IS NULL THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END) AS HardcoreCount
              FROM player_achievements AS pa
              INNER JOIN Achievements AS ach ON ach.ID = pa.achievement_id
              INNER JOIN GameData AS gd ON gd.ID = ach.GameID
              WHERE ach.Author = :author
              AND pa.user_id = :userid
              AND ach.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)";

    return legacyDbFetch($query, [
        'author' => $user->User,
        'userid' => $user->ID,
        'achievementFlag' => AchievementFlag::OfficialCore,
    ]);
}

/**
 * Gets data for other users that have earned achievements for the input user.
 */
function getObtainersOfSpecificUser(User $user): array
{
    $query = "SELECT ua.User, COUNT(ua.User) AS ObtainCount,
              SUM(CASE WHEN pa.unlocked_hardcore_at IS NULL THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END) AS HardcoreCount
              FROM player_achievements AS pa
              INNER JOIN Achievements AS ach ON ach.ID = pa.achievement_id
              INNER JOIN GameData AS gd ON gd.ID = ach.GameID
              INNER JOIN UserAccounts AS ua ON ua.User = pa.user_id
              WHERE ach.Author = :author
              AND pa.user_id != :userid
              AND ach.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)
              AND ua.Untracked = 0
              GROUP BY ua.User
              ORDER BY ObtainCount DESC";

    return legacyDbFetchAll($query, [
        'author' => $user->User,
        'userid' => $user->ID,
        'achievementFlag' => AchievementFlag::OfficialCore,
    ])->toArray();
}

/**
 * Get recent unlocks of a set of achievements
 */
function getRecentUnlocksForDev(User $user, int $offset = 0, int $count = 200): array
{
    $query = "SELECT ua.User,
                     COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) AS Date,
                     CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END AS HardcoreMode,
                     ach.ID AS AchievementID, ach.GameID, ach.Title, ach.Description,
                     ach.BadgeName, ach.Points, ach.TrueRatio,
                     gd.Title AS GameTitle, gd.ImageIcon as GameIcon, c.Name AS ConsoleName
              FROM player_achievements pa
              INNER JOIN Achievements AS ach ON ach.ID = pa.achievement_id
              INNER JOIN GameData AS gd ON gd.ID = ach.GameID
              INNER JOIN Console AS c ON c.ID = gd.ConsoleID
              INNER JOIN UserAccounts AS ua ON ua.ID = pa.user_id
              WHERE ach.Author = :author
              AND gd.ConsoleID NOT IN (100, 101)
              ORDER BY Date DESC
              LIMIT $offset, $count";

    return legacyDbFetchAll($query, [
        'author' => $user->User,
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
