<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = null;
$achievementID = (int) (requestInputQuery('a') ?? null);

if (empty($achievementID)) {
    echo json_encode([
        'Achievement' => ['ID' => null],
    ]);
    return;
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

getAchievementWonData($achievementID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, $user);

usort($winnerInfo, function ($a, $b) {
    return strtotime($a['DateAwarded']) - strtotime($b['DateAwarded']);
});

echo json_encode([
    'Achievement' => $achievement,
    'Console' => $console,
    'Game' => $game,
    'UnlocksCount' => (int) ($numWinners ?? 0),
    'TotalPlayers' => (int) ($numPossibleWinners ?? 0),
    'Unlocks' => array_values($winnerInfo ?? []),
]);
