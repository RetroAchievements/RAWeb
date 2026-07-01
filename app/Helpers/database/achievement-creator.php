<?php

use App\Models\Achievement;
use App\Models\System;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        ->sortBy([
            ['AchievementCount', 'desc'],
            ['ConsoleName'],
        ])
        ->values()
        ->toArray();
}

/**
 * Gets the number of sets worked on by the user for each console they have worked on.
 */
function getUserSetsPerConsole(User $user): array
{
    return DB::table('achievements as a')
        ->leftJoin('games as gd', 'gd.id', '=', 'a.game_id')
        ->leftJoin('systems as s', 's.id', '=', 'gd.system_id')
        ->where('a.user_id', $user->id)
        ->where('a.is_promoted', true)
        ->whereNotIn('gd.system_id', [System::Hubs, System::Events])
        ->groupBy('s.name')
        ->orderByDesc('SetCount')
        ->orderBy('s.name')
        ->selectRaw('COUNT(DISTINCT(a.game_id)) AS SetCount, s.name AS ConsoleName')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
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
 * Gets the number of time the user has obtained (casual and hardcore) their own achievements.
 */
function getOwnAchievementsObtained(User $user): array
{
    $row = DB::table('player_achievements as pa')
        ->join('achievements as ach', 'ach.id', '=', 'pa.achievement_id')
        ->join('games as gd', 'gd.id', '=', 'ach.game_id')
        ->where('ach.user_id', $user->id)
        ->where('pa.user_id', $user->id)
        ->where('ach.is_promoted', true)
        ->whereNotIn('gd.system_id', [System::Hubs, System::Events])
        ->selectRaw('SUM(CASE WHEN pa.unlocked_hardcore_at IS NULL THEN 1 ELSE 0 END) AS CasualCount')
        ->selectRaw('SUM(CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END) AS HardcoreCount')
        ->first();

    return $row ? (array) $row : [];
}

/**
 * Gets data for other users that have earned achievements for the input user.
 */
function getObtainersOfSpecificUser(User $user): array
{
    return DB::table('player_achievements as pa')
        ->join('achievements as ach', 'ach.id', '=', 'pa.achievement_id')
        ->join('games as gd', 'gd.id', '=', 'ach.game_id')
        ->join('users as ua', 'ua.id', '=', 'pa.user_id')
        ->where('ach.user_id', $user->id)
        ->where('pa.user_id', '!=', $user->id)
        ->where('ach.is_promoted', true)
        ->whereNotIn('gd.system_id', [System::Hubs, System::Events])
        ->whereNull('ua.unranked_at')
        ->groupBy('ua.username')
        ->orderByDesc('ObtainCount')
        ->selectRaw('ua.username AS User, COUNT(ua.username) AS ObtainCount')
        ->selectRaw('SUM(CASE WHEN pa.unlocked_hardcore_at IS NULL THEN 1 ELSE 0 END) AS CasualCount')
        ->selectRaw('SUM(CASE WHEN pa.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END) AS HardcoreCount')
        ->get()
        ->map(fn ($row) => (array) $row)
        ->toArray();
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
