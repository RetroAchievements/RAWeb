<?php

use App\Models\Achievement;
use App\Models\System;
use App\Models\User;

/**
 * Gets the number of achievements made by the user for each console they have worked on.
 */
function getUserAchievementsPerConsole(User $user): array
{
    $userAuthoredAchievements = $user->authoredAchievements()
        ->promoted()
        ->whereHas('game.system', function ($query) {
            $query->whereNotIn('id', [System::Hubs, System::Events]);
        })
        ->with('game.system')
        ->get();

    return $userAuthoredAchievements
        ->groupBy('game.system.name')
        ->map(function ($achievements, $systemName) {
            return [
                'ConsoleName' => $systemName,
                'AchievementCount' => $achievements->count(),
            ];
        })
        ->sortBy(function ($item) {
            return [-$item['AchievementCount'], $item['ConsoleName']];
        }, SORT_REGULAR, true)
        ->values()
        ->toArray();
}

/**
 * Gets the number of sets worked on by the user for each console they have worked on.
 */
function getUserSetsPerConsole(User $user): array
{
    $query = "SELECT COUNT(DISTINCT(a.game_id)) AS SetCount, s.name AS ConsoleName
              FROM achievements AS a
              LEFT JOIN games AS gd ON gd.id = a.game_id
              LEFT JOIN systems AS s ON s.id = gd.system_id
              WHERE a.user_id = :userId
              AND a.is_promoted = :isPromoted
              AND gd.system_id NOT IN (100, 101)
              GROUP BY ConsoleName
              ORDER BY SetCount DESC, ConsoleName";

    return legacyDbFetchAll($query, [
        'userId' => $user->id,
        'isPromoted' => 1,
    ])->toArray();
}

/**
 * Gets information for all achievements made by the user.
 */
function getUserAchievementInformation(User $user): array
{
    $userAuthoredAchievements = $user->authoredAchievements()
        ->promoted()
        ->whereHas('game.system', function ($query) {
            $query->whereNotIn('id', [System::Hubs, System::Events]);
        })
        ->with('game.system')
        ->get();

    $mappedValue = $userAuthoredAchievements->map(function (Achievement $achievement) {
        return [
            'ConsoleName' => $achievement->game->system->name,
            'GameTitle' => $achievement->game->title,
            'ID' => $achievement->id,
            'GameID' => $achievement->game->id,
            'Title' => $achievement->title,
            'Description' => $achievement->description,
            'BadgeName' => $achievement->image_name,
            'Points' => $achievement->points,
            'TrueRatio' => $achievement->points_weighted,
            'DateCreated' => $achievement->created_at->format('Y-m-d H:i:s'),
            'MemLength' => strlen($achievement->trigger_definition ?? ''),
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
              INNER JOIN achievements AS ach ON ach.id = pa.achievement_id
              INNER JOIN games AS gd ON gd.id = ach.game_id
              WHERE ach.user_id = :authorId
              AND pa.user_id = :userId
              AND ach.is_promoted = :isPromoted
              AND gd.system_id NOT IN (100, 101)";

    return legacyDbFetch($query, [
        'authorId' => $user->id,
        'userId' => $user->id,
        'isPromoted' => 1,
    ]);
}

/**
 * Gets data for other users that have earned achievements for the input user.
 */
function getObtainersOfSpecificUser(User $user): array
{
    $query = "SELECT ua.username AS User, COUNT(ua.username) AS ObtainCount,
              SUM(CASE WHEN pa.unlocked_hardcore_at IS NULL THEN 1 ELSE 0 END) AS SoftcoreCount,
              SUM(CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END) AS HardcoreCount
              FROM player_achievements AS pa
              INNER JOIN achievements AS ach ON ach.id = pa.achievement_id
              INNER JOIN games AS gd ON gd.id = ach.game_id
              INNER JOIN users AS ua ON ua.id = pa.user_id
              WHERE ach.user_id = :authorId
              AND pa.user_id != :userId
              AND ach.is_promoted = :isPromoted
              AND gd.system_id NOT IN (100, 101)
              AND ua.Untracked = 0
              GROUP BY ua.username
              ORDER BY ObtainCount DESC";

    return legacyDbFetchAll($query, [
        'authorId' => $user->id,
        'userId' => $user->id,
        'isPromoted' => 1,
    ])->toArray();
}

/**
 * Get recent unlocks of a set of achievements
 */
function getRecentUnlocksForDev(User $user, int $offset = 0, int $count = 200): array
{
    $query = "SELECT ua.username AS User,
                     COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) AS Date,
                     CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END AS HardcoreMode,
                     ach.id AS AchievementID, ach.game_id AS GameID, ach.title AS Title, ach.description AS Description,
                     ach.image_name AS BadgeName, ach.points AS Points, ach.points_weighted AS TrueRatio,
                     gd.title AS GameTitle, gd.image_icon_asset_path as GameIcon, s.name AS ConsoleName
              FROM player_achievements pa
              INNER JOIN achievements AS ach ON ach.id = pa.achievement_id
              INNER JOIN games AS gd ON gd.id = ach.game_id
              INNER JOIN systems AS s ON s.id = gd.system_id
              INNER JOIN users AS ua ON ua.id = pa.user_id
              WHERE ach.user_id = :authorId
              AND gd.system_id NOT IN (100, 101)
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
    $developerUserIdsForGame = Achievement::where('game_id', $gameId)
        ->where('is_promoted', true)
        ->distinct()
        ->pluck('user_id');

    return $developerUserIdsForGame->count() === 1 && $developerUserIdsForGame->first() === $user->id;
}
