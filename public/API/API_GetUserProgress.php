<?php

/*
 *  API_GetUserProgress - gets user's High Scores entry for a game
 *    i : CSV of game ids
 *    u : username
 *
 *  map
 *   string     [key]                       unique identifier of the game
 *    string     NumPossibleAchievements    number of core achievements for the game
 *    string     PossibleScore              maximum number of points that can be earned from the game
 *    string     NumAchieved                number of achievements earned by the user in softcore
 *    string     ScoreAchieved              number of points earned by the user in softcore
 *    string     NumAchievedHardcore        number of achievements earned by the user in hardcore
 *    string     ScoreAchievedHardcore      number of points earned by the user in hardcore
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);
$gameCSV = requestInputQuery('i', "");

getUserProgress($user, $gameCSV, $data);

echo json_encode($data, JSON_THROW_ON_ERROR);
