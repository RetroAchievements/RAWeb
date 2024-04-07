<?php

/*
 *  API_GetAchievementUnlocks
 *    a : achievement ID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 50, max: 500)
 *
 *  object     Achievement              information about the achievement
 *   string     ID                      unique identifier of the achievement
 *   string     Title                   title of the achievement
 *   string     Description             description of the achievement
 *   string     Points                  number of points the achievement is worth
 *   string     TrueRatio               number of RetroPoints ("white points") the achievement is worth
 *   string     Author                  user who first created the achievement
 *   datetime   DateCreated             when the achievement was created
 *   datetime   DateModified            when the achievement was last modified
 *   string     Type                    null, "progression", "win_condition", or "missable"
 *  object     Console                  information about the console associated to the game associated to the achievemnt
 *   string     ID                      unique identifier of the console
 *   string     Title                   name of the console
 *  object     Game                     information about the game associated to the achievement
 *   string     ID                      unique identifier of the game
 *   string     Title                   name of the game
 *  int        UnlocksCount             number of times the achievement has been unlocked
 *  int        UnlocksHardcoreCount     number of times the achievement has been unlocked in hardcore mode
 *  int        TotalPlayers             number of players who have played the game associated to the achievement
 *  array      Unlocks                  requested unlock information
 *   string     User                    user who unlocked the achievement
 *   string     RAPoints                number of points the user has
 *   datetime   DateAwarded             when the achievement was unlocked
 *   string     HardcoreMode            "1" if unlocked in hardcore, otherwise "0"
 */

$achievementID = (int) request()->query('a');
$count = min((int) request()->query('c', '50'), 500);
$offset = (int) request()->query('o');

if (empty($achievementID)) {
    return response()->json([
        'Achievement' => ['ID' => null],
    ]);
}

$achievementData = GetAchievementData($achievementID);
if ($achievementData === null) {
    return response()->json([
        'Achievement' => [],
        'UnlocksCount' => 0,
        'UnlocksHardcoreCount' => 0,
        'TotalPlayers' => 0,
        'Unlocks' => [],
    ], 404);
}

$achievement = [
    'ID' => $achievementData['AchievementID'] ?? null,
    'Title' => $achievementData['AchievementTitle'] ?? null,
    'Description' => $achievementData['Description'] ?? null,
    'Points' => $achievementData['Points'] ?? null,
    'TrueRatio' => $achievementData['TrueRatio'] ?? null,
    'Author' => $achievementData['Author'] ?? null,
    'DateCreated' => $achievementData['DateCreated'] ?? null,
    'DateModified' => $achievementData['DateModified'] ?? null,
    'Type' => $achievementData['type'] ?? null,
];

$game = [
    'ID' => $achievementData['GameID'] ?? null,
    'Title' => $achievementData['GameTitle'] ?? null,
];

$console = [
    'ID' => $achievementData['ConsoleID'] ?? null,
    'Title' => $achievementData['ConsoleName'] ?? null,
];

$parentGame = getParentGameFromGameTitle($game['Title'], $achievementData['ConsoleID']);

$unlocks = getAchievementUnlocksData($achievementID, null, $numWinners, $numWinnersHardcore, $numPossibleWinners, $parentGame?->id, $offset, $count);

return response()->json([
    'Achievement' => $achievement,
    'Console' => $console,
    'Game' => $game,
    'UnlocksCount' => $numWinners ?? 0,
    'UnlocksHardcoreCount' => $numWinnersHardcore ?? 0,
    'TotalPlayers' => $numPossibleWinners ?? 0,
    'Unlocks' => $unlocks->values(),
]);
