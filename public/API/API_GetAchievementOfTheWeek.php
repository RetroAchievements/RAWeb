<?php

/*
 *  API_GetAchievementOfTheWeek
 *   (no inputs)
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
 *  object     ForumTopic      information about the game's official forum topic
 *   string     ID             unique identifier of the game's official forum topic
 *  datetime   StartAt         when the achievement was set as the achievement of the week
 *  int        UnlocksCount    number of times the achievement has been unlocked
 *  int        TotalPlayers    number of players who have played the game associated to the achievement
 *  array      Unlocks         requested unlock information
 *   string     User           user who unlocked the achievement
 *   string     RAPoints       number of points the user has
 *   datetime   DateAwarded    when the achievement was unlocked
 *   string     HardcoreMode   "1" if unlocked in hardcore, otherwise "0"
 */

/*
 * NOTE: this is just a copy of API_GetAchievementUnlocks that is hardcoded to query the
 *       achievement of the week data. It adds StartAt and ForumTopic to the output and
 *       filters the Unlocks to just those entries after StartAt.
 */

$staticData = getStaticData();
$user = null;
$achievementID = (int) ($staticData['Event_AOTW_AchievementID'] ?? null);
$startAt = $staticData['Event_AOTW_StartAt'] ?? null;

if (empty($achievementID)) {
    return response()->json([
        'Achievement' => ['ID' => null],
        'StartAt' => null,
    ]);
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

getAchievementUnlocksData($achievementID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, $user, 0, 500);

/**
 * reset unlocks if there is no start date to prevent listing invalid entries
 */
if (empty($startAt)) {
    $winnerInfo = [];
}

if (!empty($startAt)) {
    $winnerInfo = array_filter($winnerInfo, fn ($unlock) => (int) strtotime($unlock['DateAwarded']) >= (int) strtotime($startAt));
}

// reverse order so newest winners are last
usort($winnerInfo, fn ($a, $b) => strtotime($a['DateAwarded']) - strtotime($b['DateAwarded']));

return response()->json([
    'Achievement' => $achievement,
    'Console' => $console,
    'ForumTopic' => $forumTopic,
    'Game' => $game,
    'StartAt' => $startAt,
    'TotalPlayers' => $numPossibleWinners ?? 0,
    'Unlocks' => array_values($winnerInfo ?? []),
    'UnlocksCount' => $numWinners ?? 0,
]);
