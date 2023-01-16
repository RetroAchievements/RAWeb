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

$user = request()->query('u');

$result = [];
$completedGames = getUsersCompletedGamesAndMax($user);
foreach ($completedGames as $completedGame) {
    if ($completedGame['NumAwarded'] > 0) {
        $result[] = [
            'GameID' => $completedGame['GameID'],
            'Title' => $completedGame['Title'],
            'ImageIcon' => $completedGame['ImageIcon'],
            'ConsoleID' => $completedGame['ConsoleID'],
            'ConsoleName' => $completedGame['ConsoleName'],
            'MaxPossible' => $completedGame['MaxPossible'],
            'NumAwarded' => $completedGame['NumAwarded'],
            'PctWon' => $completedGame['PctWon'],
            'HardcoreMode' => '0',
        ];
    }
    if ($completedGame['NumAwardedHC'] > 0) {
        $result[] = [
            'GameID' => $completedGame['GameID'],
            'Title' => $completedGame['Title'],
            'ImageIcon' => $completedGame['ImageIcon'],
            'ConsoleID' => $completedGame['ConsoleID'],
            'ConsoleName' => $completedGame['ConsoleName'],
            'MaxPossible' => $completedGame['MaxPossible'],
            'NumAwarded' => $completedGame['NumAwardedHC'],
            'PctWon' => $completedGame['PctWonHC'],
            'HardcoreMode' => '1',
        ];
    }
}

return response()->json($result);
