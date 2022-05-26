<?php

/*
 *  API_GetUserCompletedGames - gets all game progress for a user
 *    u : username
 *
 *    NOTE: each game may appear in the list twice - once for Hardcore and once for Casual
 *
 *  array
 *   object     [value]
 *    string     GameID           unique identifier of the game
 *    string     Title            title of the game
 *    string     ImageIcon        site-relative path to the game's image icon
 *    string     ConsoleID        unique identifier of the console associated to the game
 *    string     ConsoleName      name of the console associated to the game
 *    string     MaxPossible      number of core achievements associated to the game
 *    string     NumAwarded       number of achievements earned by the user
 *    string     PctWon           NumAwarded divided by MaxPossible
 *    string     HardcoreMode     "1" if the data is for hardcore, otherwise "0"
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);

$data = getUsersCompletedGamesAndMax($user);

echo json_encode($data, JSON_THROW_ON_ERROR);
