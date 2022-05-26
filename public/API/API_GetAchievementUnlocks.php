<?php

/*
 *  API_GetAchievementUnlocks
 *    a : achievement ID
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 50, max: 500)
 *
 *  object     Achievement     information about the achievement
 *   string     ID             unique identifier of the achievement
 *   string     Title          title of the achievement
 *   string     Description    description of the achievement
 *   string     Points         number of points the achievement is worth
 *   string     TrueRatio      number of "white" points the achievement is worth
 *   string     Author         user who first created the achievement
 *   datetime   DateCreated    when the achievement was created
 *   datetime   DateModified   when the achievement was last modified
 *  object     Console         information about the console associated to the game associated to the achievemnt
 *   string     ID             unique identifier of the console
 *   string     Title          name of the console
 *  object     Game            information about the game associated to the achievement
 *   string     ID             unique identifier of the game
 *   string     Title          name of the game
 *  int        UnlocksCount    number of times the achievement has been unlocked
 *  int        TotalPlayers    number of players who have played the game associated to the achievement
 *  array      Unlocks         requested unlock information
 *   string     User           user who unlocked the achievement
 *   string     RAPoints       number of points the user has
 *   datetime   DateAwarded    when the achievement was unlocked
 *   string     HardcoreMode   "1" if unlocked in hardcore, otherwise "0"
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = null;
$achievementID = (int) (requestInputQuery('a') ?? null);
$count = min(requestInputQuery('c', 50), 500);
$offset = requestInputQuery('o', 0);

if (empty($achievementID)) {
    echo json_encode([
        'Achievement' => ['ID' => null],
    ]);
    exit;
}

$achievementData = GetAchievementMetadataJSON($achievementID);

$achievement = [
    'ID' => $achievementData['AchievementID'] ?? null,
    'Title' => $achievementData['AchievementTitle'] ?? null,
    'Description' => $achievementData['Description'] ?? null,
    'Points' => $achievementData['Points'] ?? null,
    'TrueRatio' => $achievementData['TrueRatio'] ?? null,
    'Author' => $achievementData['Author'] ?? null,
    'DateCreated' => $achievementData['DateCreated'] ?? null,
    'DateModified' => $achievementData['DateModified'] ?? null,
];

$game = [
    'ID' => $achievementData['GameID'] ?? null,
    'Title' => $achievementData['GameTitle'] ?? null,
];

$console = [
    'ID' => $achievementData['ConsoleID'] ?? null,
    'Title' => $achievementData['ConsoleName'] ?? null,
];

getAchievementWonData($achievementID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, $user, $offset, $count);

echo json_encode([
    'Achievement' => $achievement,
    'Console' => $console,
    'Game' => $game,
    'UnlocksCount' => (int) ($numWinners ?? 0),
    'TotalPlayers' => (int) ($numPossibleWinners ?? 0),
    'Unlocks' => array_values($winnerInfo ?? []),
], JSON_THROW_ON_ERROR);
