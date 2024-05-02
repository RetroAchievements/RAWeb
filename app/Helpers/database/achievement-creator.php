<?php

use App\Models\Achievement;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;

/**
 * Gets the number of achievements made by the user for each console they have worked on.
 */
function getUserAchievementsPerConsole(User $user): array
{
    $userAuthoredAchievements = Achievement::whereHas("game.system", function ($query) {
        $query->whereNotIn("ID", [System::Hubs, System::Events]);
      })
        ->with("game.system")
        ->where("user_id", $user->id)
        ->where("Flags", AchievementFlag::OfficialCore)
        ->get();

    return $userAuthoredAchievements
        ->groupBy('game.system.Name')
        ->map(function ($achievements, $systemName) {
            return [
                'ConsoleName' => $systemName,
                'AchievementCount' => $achievements->count(),
            ];
        })
        ->sortByDesc('AchievementCount')
        ->values()
        ->toArray();
}

/**
 * Gets the number of sets worked on by the user for each console they have worked on.
 */
function getUserSetsPerConsole(User $user): array
{
    $query = "SELECT COUNT(DISTINCT(a.GameID)) AS SetCount, c.Name AS ConsoleName
              FROM Achievements AS a
              LEFT JOIN GameData AS gd ON gd.ID = a.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE a.user_id = :userId
              AND a.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)
              GROUP BY ConsoleName
              ORDER BY SetCount DESC, ConsoleName";

    return legacyDbFetchAll($query, [
        'userId' => $user->id,
        'achievementFlag' => AchievementFlag::OfficialCore,
    ])->toArray();
}

/**
 * Gets information for all achievements made by the user.
 */
function getUserAchievementInformation(User $user): array
{
    $userAuthoredAchievements = Achievement::whereHas("game.system", function ($query) {
        $query->whereNotIn("ID", [System::Hubs, System::Events]);
      })
        ->with("game.system")
        ->where("user_id", $user->id)
        ->where("Flags", AchievementFlag::OfficialCore)
        ->get();

    $mappedValue = $userAuthoredAchievements->map(function (Achievement $achievement) {
        return [
            'ConsoleName' => $achievement->game->system->Name,
            'GameTitle' => $achievement->game->title,
            'ID' => $achievement->id,
            'GameID' => $achievement->game->id,
            'Title' => $achievement->title,
            'Description' => $achievement->description,
            'BadgeName' => $achievement->badge_name,
            'Points' => $achievement->points,
            'TrueRatio' => $achievement->points_weighted,
            'DateCreated' => $achievement->DateCreated->format('Y-m-d H:i:s'),
            'MemLength' => strlen($achievement->MemAddr ?? ''),
        ];
    });

    return $mappedValue->toArray();
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
              WHERE ach.user_id = :authorId
              AND pa.user_id = :userId
              AND ach.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)";

    return legacyDbFetch($query, [
        'authorId' => $user->id,
        'userId' => $user->id,
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
              INNER JOIN UserAccounts AS ua ON ua.ID = pa.user_id
              WHERE ach.user_id = :authorId
              AND pa.user_id != :userId
              AND ach.Flags = :achievementFlag
              AND gd.ConsoleID NOT IN (100, 101)
              AND ua.Untracked = 0
              GROUP BY ua.User
              ORDER BY ObtainCount DESC";

    return legacyDbFetchAll($query, [
        'authorId' => $user->id,
        'userId' => $user->id,
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
              WHERE ach.user_id = :authorId
              AND gd.ConsoleID NOT IN (100, 101)
              ORDER BY Date DESC
              LIMIT $offset, $count";

    return legacyDbFetchAll($query, [
        'authorId' => $user->id,
    ])->toArray();
}

/**
 * Checks to see if a user is the sole author of a set.
 */
function checkIfSoleDeveloper(User $user, int $gameId): bool
{
    $developerUserIdsForGame = Achievement::where('GameID', $gameId)
        ->where('Flags', AchievementFlag::OfficialCore)
        ->distinct()
        ->pluck('user_id');

    return $developerUserIdsForGame->count() === 1 && $developerUserIdsForGame->first() === $user->id;
}
