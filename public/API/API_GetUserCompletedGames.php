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
 *    string     NumAwarded       number of achievements earned by the user in softcore mode
 *    string     NumAwardedHC     number of achievements earned by the user in hardcore mode
 *    string     PctWon           NumAwarded divided by MaxPossible in softcore mode
 *    string     PctWonHC         NumAwarded divided by MaxPossible in hardcore mode
 *    string     HardcoreMode     "1" if the data is for hardcore, otherwise "0"
 */

$user = request()->query('u');

return response()->json(getUsersCompletedGamesAndMax($user));
