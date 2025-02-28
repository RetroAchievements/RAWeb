<?php

/*
 *  API_GetUserCompletedGames - gets all game progress for a user
 *    u : username or user ULID
 *
 *    NOTE: each game may appear in the list twice - once for Hardcore and once for Casual
 *
 *  array
 *   object     [value]
 *    int        GameID           unique identifier of the game
 *    string     Title            title of the game
 *    string     ImageIcon        site-relative path to the game's image icon
 *    int        ConsoleID        unique identifier of the console associated to the game
 *    string     ConsoleName      name of the console associated to the game
 *    int        MaxPossible      number of core achievements associated to the game
 *    string     NumAwarded       number of achievements earned by the user
 *    string     PctWon           NumAwarded divided by MaxPossible
 *    string     HardcoreMode     "1" if the data is for hardcore, otherwise "0"
 */

use App\Actions\FindUserByIdentifierAction;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', new ValidUserIdentifier()],
]);

$user = (new FindUserByIdentifierAction())->execute($input['u']);

$result = [];
$completedGames = getUsersCompletedGamesAndMax($user?->username ?? "");
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
