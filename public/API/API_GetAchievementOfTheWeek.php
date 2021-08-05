<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$staticData = getStaticData();
$user = null;
$achievementID = (int) ($staticData['Event_AOTW_AchievementID'] ?? null);
$startAt = $staticData['Event_AOTW_StartAt'] ?? null;

if (empty($achievementID)) {
    echo json_encode([
        'Achievement' => ['ID' => null],
        'StartAt' => null,
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

$forumTopic = [
    'ID' => $staticData['Event_AOTW_ForumID'] ?? null,
];

getAchievementWonData($achievementID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, $user, 0, 500);

/**
 * reset unlocks if there is no start date to prevent listing invalid entries
 */
if (empty($startAt)) {
    $winnerInfo = [];
}

if (!empty($startAt)) {
    $winnerInfo = array_filter($winnerInfo, function ($unlock) use ($startAt) {
        return (int) strtotime($unlock['DateAwarded']) >= (int) strtotime($startAt);
    });
}

usort($winnerInfo, function ($a, $b) {
    return strtotime($a['DateAwarded']) - strtotime($b['DateAwarded']);
});

echo json_encode([
    'Achievement' => $achievement,
    'Console' => $console,
    'ForumTopic' => $forumTopic,
    'Game' => $game,
    'StartAt' => $startAt,
    'TotalPlayers' => (int) ($numPossibleWinners ?? 0),
    'Unlocks' => array_values($winnerInfo ?? []),
    'UnlocksCount' => (int) ($numWinners ?? 0),
]);
