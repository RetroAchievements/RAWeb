<?php

/*
 *  API_GetUserGameRankAndScore - gets user's High Scores entry for a game
 *    g : game id
 *    u : username
 *
 *  array
 *   object     [value]
 *    string     User             name of user
 *    string     TotalScore       total number of points earned by the user for the game
 *    datetime   LastAward        when the last achievement was unlocked for the user
 *    string     UserRank         position of user on the game's High Scores list
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameId = requestInputQuery('g');
$username = requestInputQuery('u');

$results = getGameRankAndScore($gameId, $username);

echo json_encode($results, JSON_THROW_ON_ERROR);
