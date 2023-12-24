<?php

/*
 *  API_GetUserCompletionProgress - gets the entire completion progress (eg: /user/{username}/progress) for a user
 *                                  similar to `GetUserCompletedGames`, but only includes a single record for each game
 *                                  and also includes the game's current award level as shown on the "Completion Progress" page.
 *    u : username
 *
 *  int         Count                       number of game completion records returned in the response
 *  int         Total                       number of game completion records the user actually has overall
 *  array       Results
 *   object      [value]
 *    int         GameID                    unique identifier of the game
 *    string      Title                     title of the game
 *    string      ImageIcon                 site-relative path to the game's image icon
 *    int         ConsoleID                 unique identifier of the console associated to the game
 *    string      ConsoleName               name of the console associated to the game
 *    int         MaxPossible               number of core achievements associated to the game
 *    int         NumAwarded                number of softcore achievements earned by the user for the game
 *    int         NumAwardedHardcore        number of hardcore achievements earned by the user for the game
 *    ?datetime   MostRecentAwardedDate     an ISO8601 timestamp string, or null, for when the most recent achievement was unlocked by the user
 *    ?string     HighestAwardKind          "mastered", "completed", "beaten-hardcore", "beaten-softcore", or null
 *    ?datetime   HighestAwardDate          an ISO8601 timestamp string, or null, for when the HighestAwardKind was granted
 */

use App\Platform\Services\PlayerProgressionService;
use App\Support\Rules\CtypeAlnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'u' => ['required', 'min:2', 'max:20', new CtypeAlnum()],
]);

$user = request()->query('u');

$playerProgressionService = new PlayerProgressionService();

$userGamesList = getUsersCompletedGamesAndMax($user);
$userSiteAwards = getUsersSiteAwards($user);
$filteredAndJoinedGamesList = $playerProgressionService->filterAndJoinGames(
    $userGamesList,
    $userSiteAwards,
);

$results = [];
foreach ($filteredAndJoinedGamesList as $game) {
    $results[] = [
        'GameID' => $game['GameID'],
        'Title' => $game['Title'],
        'ImageIcon' => $game['ImageIcon'],
        'ConsoleID' => $game['ConsoleID'],
        'ConsoleName' => $game['ConsoleName'],
        'MaxPossible' => $game['MaxPossible'],
        'NumAwarded' => $game['NumAwarded'],
        'NumAwardedHardcore' => $game['NumAwardedHC'] ?? 0,
        'MostRecentAwardedDate' => isset($game['MostRecentWonDate']) ? (new Carbon($game['MostRecentWonDate'], 'UTC'))->toIso8601String() : null,
        'HighestAwardKind' => $game['HighestAwardKind'] ?? null,
        'HighestAwardDate' => isset($game['HighestAwardDate']) ? Carbon::createFromTimestamp($game['HighestAwardDate'], 'UTC')->toIso8601String() : null,
    ];
}

// Sort the results by MostRecentAwardedDate
usort($results, function ($a, $b) {
    $dateA = $a['MostRecentAwardedDate'] ? new Carbon($a['MostRecentAwardedDate']) : null;
    $dateB = $b['MostRecentAwardedDate'] ? new Carbon($b['MostRecentAwardedDate']) : null;

    if (!$dateA && !$dateB) {
        return 0;
    } elseif (!$dateA) {
        return 1;
    } elseif (!$dateB) {
        return -1;
    } else {
        // We'll put the entities in descending order.
        return $dateB <=> $dateA;
    }
});

return response()->json([
    // `Count` and `Total` are the same right now, but for future-facing & backwards-compatibility
    // purposes, we may want to add pagination to this endpoint at a later date without worrying
    // about breaking current consumers.
    'Count' => count($results),
    'Total' => count($results),

    'Results' => $results,
]);
