<?php

/*
 *  API_GetGameExtended - returns information about a game
 *    i : game id
 *    f : flag - 3 for core achievements, 5 for unofficial (default: 3)
 *
 *  int        ID                         unique identifier of the game
 *  string     Title                      name of the game
 *  int        ConsoleID                  unique identifier of the console associated to the game
 *  string     ConsoleName                name of the console associated to the game
 *  int?       ParentGameID               unique identifier of the parent game if this is a subset
 *  int        NumDistinctPlayers         number of unique players who have earned achievements for the game
 *  int        NumDistinctPlayersCasual   [deprecated] equal to NumDistinctPlayers
 *  int        NumDistinctPlayersHardcore [deprecated] equal to NumDistinctPlayers
 *  int        NumAchievements            count of core achievements associated to the game
 *  map        Achievements
 *   string     [key]                     unique identifier of the achievement
 *    int        ID                       unique identifier of the achievement
 *    string     Title                    title of the achievement
 *    string     Description              description of the achievement
 *    int        Points                   number of points the achievement is worth
 *    int        TrueRatio                number of "white" points the achievement is worth
 *    string     BadgeName                unique identifier of the badge image for the achievement
 *    int        NumAwarded               number of times the achievement has been awarded
 *    int        NumAwardedHardcore       number of times the achievement has been awarded in hardcore
 *    int        DisplayOrder             field used for determining which order to display the achievements
 *    string     Author                   user who originally created the achievement
 *    datetime   DateCreated              when the achievement was created
 *    datetime   DateModified             when the achievement was last modified
 *    string     MemAddr                  md5 of the logic for the achievement
 *  int        ForumTopicID               unique identifier of the official forum topic for the game
 *  int        Flags                      always "0"
 *  string     ImageIcon                  site-relative path to the game's icon image
 *  string     ImageTitle                 site-relative path to the game's title image
 *  string     ImageIngame                site-relative path to the game's in-game image
 *  string     ImageBoxArt                site-relative path to the game's box art image
 *  string     Publisher                  publisher information for the game
 *  string     Developer                  developer information for the game
 *  string     Genre                      genre information for the game
 *  string     Released                   release date information for the game
 *  bool       IsFinal
 *  string     RichPresencePatch          md5 of the script for generating the rich presence for the game
 *  array      Claims
 *   object    [value]
 *    string    User                      user holding the claim
 *    int       SetType                   set type claimed: 0 - new set, 1 - revision
 *    int       ClaimType                 claim type: 0 - primary, 1 - collaboration
 *    string    Created                   date the claim was made
 *    string    Expiration                date the claim will expire
 */

use App\Community\Models\AchievementSetClaim;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use Carbon\Carbon;

$gameID = (int) request()->query('i');
$flag = (int) request()->query('f', (string) AchievementFlag::OfficialCore);

$game = Game::with('system')->find($gameID);
$gameSetClaims = AchievementSetClaim::find($gameID);

// TODO move to seprate file
if (!function_exists('getParentGameId')) {
    function getParentGameId(string $title, int $consoleID, int $gameID): ?int
    {

        $result = Game::where('Title', 'LIKE', '%' . $title . '%')->where('ConsoleID', '=', $consoleID)->first();

        if ($result->Title = $title) {
            return null;
        } else {
            $matchValue = $result->Title;
            $remainingValue = preg_replace('/(?:^|\s)\[.*Subset - .*\](?:\s|$)/', '', $matchValue);
            $remainingResult = Game::where('Title', $remainingValue)->first();

            return (int) $remainingResult->ID ? $remainingResult->ID : null;
        }
    }
}

/* Maps
    These return the data in the order that the v1 api expects, originally
    the endpoint used function calls that made queries with the data in the
    order that the endpoint returns but the Eloquent collections order the
    data by how the columns are laid out in the database. This mapping adds
    between 20-30ms to the response time on average. Average 90ms on the old
    implementation vs average 110ms on this refactor.
*/

if ($game) {
    $gameData =
        [
            'ID' => $game->ID,
            'Title' => $game->Title,
            'ConsoleID' => $game->ConsoleID,
            'ForumTopicID' => $game->ForumTopicID,
            'Flags' => null, // Always '0', this is different in the extended endpoint test for some reason
            'ImageIcon' => $game->ImageIcon,
            'ImageTitle' => $game->ImageTitle,
            'ImageIngame' => $game->ImageIngame,
            'ImageBoxArt' => $game->ImageBoxArt,
            'Publisher' => $game->Publisher,
            'Developer' => $game->Developer,
            'Genre' => $game->Genre,
            'Released' => $game->Released,
            'IsFinal' => $game->IsFinal,
            'RichPresencePatch' => md5($game->RichPresencePatch),
            'GuideURL' => $game->GuideURL,
            'Updated' => $game->Updated,
        ];
    } else {
    return response()->json();
}

if (!$game->achievements->where('Flags', $flag)->isEmpty()) {
    $gameAchievements = $game->achievements->where('Flags', $flag)->keyBy('ID')->map(function ($am) {
        return [
            'ID' => $am->ID,
            'NumAwarded' => (int) Achievement::find($am->ID)->unlocks_total,
            'NumAwardedHardcore' => (int) Achievement::find($am->ID)->unlocks_hardcore_total,
            'Title' => $am->Title,
            'Description' => $am->Description,
            'Points' => $am->Points,
            'TrueRatio' => $am->TrueRatio,
            'Author' => $am->Author,
            'DateModified' => Carbon::parse($am->DateModified)->format('Y-m-d H:i:s'),
            'DateCreated' => Carbon::parse($am->DateCreated)->format('Y-m-d H:i:s'),
            'BadgeName' => $am->BadgeName,
            'DisplayOrder' => $am->DisplayOrder,
            'MemAddr' => md5(Achievement::find($am->ID)->MemAddr),
            'type' => $am->type,
        ];
    })->sortBy('DisplayOrder');
} else {
    $gameAchievements = new ArrayObject();
}
//dd($gameAchievements);

if ($gameSetClaims) {
    $gameClaims =
        [[
            'User' => $gameSetClaims->User,
            'SetType' => $gameSetClaims->SetType,
            'GameID' => $gameSetClaims->GameID,
            'ClaimType' => $gameSetClaims->ClaimType,
            'Created' => Carbon::parse($gameSetClaims->Created)->format('Y-m-d H:i:s'),
            'Expiration' => Carbon::parse($gameSetClaims->Finished)->format('Y-m-d H:i:s'),
        ]];
} else {
    $gameClaims = [];
}

$getGameExtended = array_merge(
    $gameData,
    ['ConsoleName' => $game->system->Name],
    ['ParentGameID' => getParentGameId($game->Title, $game->ConsoleID, $gameID)],
    ['NumDistinctPlayers' => count($game->players)],
    ['NumAchievements' => count($gameAchievements)],
    ['Achievements' => $gameAchievements],
    ['Claims' => $gameClaims],
    ['NumDistinctPlayersCasual' => count($game->players)], // Deprecated - Only here to maintain API V1 compat
    ['NumDistinctPlayersHardcore' => count($game->players)], // Deprecated - Only here to maintain API V1 compat
);

return response()->json($getGameExtended);
