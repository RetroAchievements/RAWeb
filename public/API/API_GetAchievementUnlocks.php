<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$user = null;
$achievementID = (int)(seekGET('a') ?? null);

if (empty($achievementID)) {
    echo json_encode([
        'Achievement' => ['ID' => null],
    ]);
    return;
}

$gameId = null;
$gameTitle = null;
$achievementTitle = getAchievementTitle($achievementID, $gameTitle, $gameId);
$achievement = [
    'ID' => $achievementID,
    'Title' => $achievementTitle,
];

$game = [
    'ID' => $gameId,
    'Title' => $gameTitle,
];

getAchievementWonData($achievementID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, $user);

usort($winnerInfo, function ($a, $b) {
    return strtotime($a['DateAwarded']) - strtotime($b['DateAwarded']);
});

echo json_encode([
    'Achievement' => $achievement,
    'Game' => $game,
    'UnlocksCount' => (int)($numWinners ?? 0),
    'TotalPlayers' => (int)($numPossibleWinners ?? 0),
    'Unlocks' => array_values($winnerInfo ?? []),
]);
