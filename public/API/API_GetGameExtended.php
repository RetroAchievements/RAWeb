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

if (!function_exists('getParentGameId')) {
function getParentGameId(string $title, int $consoleID, int $gameID): ?int
{

    $result = Game::where('Title', 'LIKE', '%' . $title . '%')->where('ConsoleID', '=', $consoleID)->first();

    if ($result) {
        $matchValue = $result->Title;
        $remainingValue = preg_replace('/(?:^|\s)\[.*Subset - .*\](?:\s|$)/', '', $matchValue);
        $remainingResult = Game::where('Title', $remainingValue)->first();

        if ($gameID == $remainingResult->ID) {
            return null;
        }

        return $remainingResult->ID ? $remainingResult->ID : null;
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

if (Game::where('ID', $gameID)->exists()) {
    $gameData = collect([Game::find($gameID)])->map(function ($gd) {
        return [
            'ID' => $gd->ID,
            'Title' => $gd->Title,
            'ConsoleID' => $gd->ConsoleID,
            'ForumTopicID' => $gd->ForumTopicID,
            'Flags' => null, // Always '0', this is different in the extended endpoint test for some reason
            'ImageIcon' => $gd->ImageIcon,
            'ImageTitle' => $gd->ImageTitle,
            'ImageIngame' => $gd->ImageIngame,
            'ImageBoxArt' => $gd->ImageBoxArt,
            'Publisher' => $gd->Publisher,
            'Developer' => $gd->Developer,
            'Genre' => $gd->Genre,
            'Released' => $gd->Released,
            'IsFinal' => $gd->IsFinal,
            'RichPresencePatch' => md5($gd->RichPresencePatch),
            'GuideURL' => $gd->GuideURL,
            'Updated' => $gd->Updated,
        ];
    })->first();
} else {
    return response()->json();
}

if (!Game::find($gameID)->achievements->isEmpty()) {
    $gameAchievements = Game::find($gameID)->achievements->where('Flags', $flag)->map(function ($am) {
        return [
            'ID' => $am->ID,
            'NumAwarded' => Achievement::find($am->ID)->unlocks_total,
            'NumAwardedHardcore' => Achievement::find($am->ID)->unlocks_hardcore_total,
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
    })->keyBy('ID');
    $gameAchievements->sortBy('DisplayOrder');
} else {
    $gameAchievements = new ArrayObject();
}

if (AchievementSetClaim::where('GameID', $gameID)->get()) {
    $gameClaims = AchievementSetClaim::where('GameID', $gameID)->get()->map(function ($gc) {
        return [
            'User' => $gc->User,
            'SetType' => $gc->SetType,
            'GameID' => $gc->GameID,
            'ClaimType' => $gc->ClaimType,
            'Created' => Carbon::parse($gc->Created)->format('Y-m-d H:i:s'),
            'Expiration' => Carbon::parse($gc->Finished)->format('Y-m-d H:i:s'),
        ];
    });
} else {
    $gameClaims = [];
}

$getGameExtended = array_merge(
    $gameData,
    ['ConsoleName' => Game::find($gameID)->system->Name],
    ['ParentGameID' => getParentGameId(Game::find($gameID)->Title, Game::find($gameID)->ConsoleID, $gameID)],
    ['NumDistinctPlayers' => count(Game::find($gameID)->players)],
    ['NumAchievements' => count($gameAchievements)],
    ['Achievements' => $gameAchievements],
    ['Claims' => $gameClaims],
    ['NumDistinctPlayersCasual' => count(Game::find($gameID)->players)], // Deprecated - Only here to maintain API V1 compat
    ['NumDistinctPlayersHardcore' => count(Game::find($gameID)->players)], // Deprecated - Only here to maintain API V1 compat
);

return response()->json($getGameExtended);