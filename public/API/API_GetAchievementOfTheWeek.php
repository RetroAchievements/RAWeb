<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$staticData = getStaticData();
$user = null;
$achievementID = (int)($staticData['Event_AOTW_AchievementID'] ?? null);
$startAt = $staticData['Event_AOTW_StartAt'] ?? null;

if (empty($achievementID)) {
    echo json_encode([
        'Achievement' => ['ID' => null],
        'StartAt' => null,
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

$forumTopic = [
    'ID' => $staticData['Event_AOTW_ForumID'] ?? null,
];

getAchievementWonData($achievementID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, $user);

/**
 * reset unlocks if there is no start date to prevent listing invalid entries
 */
if(empty($startAt)) {
    $winnerInfo = [];
}

if(!empty($startAt)) {
    $winnerInfo = array_filter($winnerInfo, function($unlock) use ($startAt) {
        return strtotime($unlock['DateAwarded']) >= strtotime($startAt);
    });
}

usort($winnerInfo, function($a, $b){
    return strtotime($a['DateAwarded']) - strtotime($b['DateAwarded']);
});

echo json_encode([
    'Achievement' => $achievement,
    'ForumTopic' => $forumTopic,
    'Game' => $game,
    'StartAt' => $startAt,
    'TotalPlayers' => (int)($numPossibleWinners ?? 0),
    'Unlocks' => array_values($winnerInfo ?? []),
    'UnlocksCount' => (int)($numWinners ?? 0),
]);
