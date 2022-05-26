<?php

/*
 *  API_GetUserRecentlyPlayedGames
 *    u : username
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of games to return (default: 10, max: 50)
 *
 *  array
 *   object     [value]
 *    string     GameID                   unique identifier of the game
 *    string     Title                    name of the game
 *    string     NumPossibleAchievements  count of core achievements associated to the game
 *    string     PossibleScore            total points the game's achievements are worth
 *    int        ConsoleID                unique identifier of the console associated to the game
 *    string     ConsoleName              name of the console associated to the game
 *    string     ImageIcon                site-relative path to the game's icon image
 *    datetime   LastPlayed               when the user last played the game
 *    string     MyVote                   user's rating of the game (1-5)
 *    int        NumAchieved              number of achievements earned by the user
 *    int        ScoreAchieved            number of points earned by the user
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);
$count = min(requestInputQuery('c', 10), 50);
$offset = requestInputQuery('o', 0);

$recentlyPlayedData = [];
$numRecentlyPlayed = getRecentlyPlayedGames($user, $offset, $count, $recentlyPlayedData);

if (!empty($recentlyPlayedData)) {
    $gameIDsCSV = $recentlyPlayedData[0]['GameID'];
    for ($i = 1; $i < $numRecentlyPlayed; $i++) {
        $gameIDsCSV .= ", " . $recentlyPlayedData[$i]['GameID'];
    }

    getUserProgress($user, $gameIDsCSV, $awardedData);

    $iter = 0;
    foreach ($awardedData as $nextAwardID => $nextAwardData) {
        $recentlyPlayedData[$iter]['NumPossibleAchievements'] = $nextAwardData['NumPossibleAchievements'];
        $recentlyPlayedData[$iter]['PossibleScore'] = $nextAwardData['PossibleScore'];
        $recentlyPlayedData[$iter]['NumAchieved'] = $nextAwardData['NumAchieved'];
        $recentlyPlayedData[$iter]['ScoreAchieved'] = $nextAwardData['ScoreAchieved'];
        $iter++; // Assumes a LOT about the order of this array!
    }

    $libraryOut['Awarded'] = $awardedData;
}

echo json_encode($recentlyPlayedData, JSON_THROW_ON_ERROR);
