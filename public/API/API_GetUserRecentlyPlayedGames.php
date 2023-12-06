<?php

/*
 *  API_GetUserRecentlyPlayedGames
 *    u : username
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of games to return (default: 10, max: 50)
 *
 *  array
 *   object     [value]
 *    int        GameID                   unique identifier of the game
 *    string     Title                    name of the game
 *    int        NumPossibleAchievements  count of core achievements associated to the game
 *    string     PossibleScore            total points the game's achievements are worth
 *    int        ConsoleID                unique identifier of the console associated to the game
 *    string     ConsoleName              name of the console associated to the game
 *    string     ImageIcon                site-relative path to the game's icon image
 *    string     ImageTitle               title image for the game
 *    string     ImageIngame              gameplay image for the game
 *    string     ImageBoxArt              box art image for the game
 *    datetime   LastPlayed               when the user last played the game
 *    int        NumAchieved              number of achievements earned by the user in softcore
 *    string     ScoreAchieved            number of points earned by the user in softcore
 *    int        NumAchievedHardcore      number of achievements earned by the user in hardcore
 *    string     ScoreAchievedHardcore    number of points earned by the user in hardcore
 */

use App\Site\Models\User;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
    'c' => 'nullable|integer|min:0',
    'o' => 'nullable|integer|min:0',
]);

$user = User::firstWhere('User', request()->query('u'));
if (!$user) {
    return response()->json([]);
}

$count = min((int) request()->query('c', '10'), 50);
$offset = (int) request()->query('o');

$recentlyPlayedData = [];
$numRecentlyPlayed = getRecentlyPlayedGames($user->User, $offset, $count, $recentlyPlayedData);

if (!empty($recentlyPlayedData)) {
    $gameIDs = [];
    foreach ($recentlyPlayedData as $recentlyPlayed) {
        $gameIDs[] = $recentlyPlayed['GameID'];
    }

    $awardedData = getUserProgress($user, $gameIDs);

    foreach ($awardedData['Awarded'] as $nextAwardID => $nextAwardData) {
        $entry = array_search($nextAwardID, array_column($recentlyPlayedData, 'GameID'));
        if ($entry !== false) {
            $recentlyPlayedData[$entry] = array_merge($recentlyPlayedData[$entry], $nextAwardData);
        }
    }
}

return response()->json($recentlyPlayedData);
