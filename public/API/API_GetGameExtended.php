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
 *    int        TrueRatio                number of RetroPoints ("white points") the achievement is worth
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

use App\Platform\Enums\AchievementFlag;

$gameID = (int) request()->query('i');
$flag = (int) request()->query('f', (string) AchievementFlag::OfficialCore);
getGameMetadata($gameID, null, $achData, $gameData, flag: $flag, metrics: true);

if ($gameData === null) {
    return response()->json();
}

if (empty($achData)) {
    $gameData['Achievements'] = new ArrayObject(); // issue #484 - force serialization to {}
} else {
    foreach ($achData as &$achievement) {
        $achievement['MemAddr'] = md5($achievement['MemAddr'] ?? null);
    }
    $gameData['Achievements'] = $achData;
}
$gameData['Claims'] = getClaimData($gameID, false);
$gameData['RichPresencePatch'] = md5($gameData['RichPresencePatch'] ?? null);

$gameData['NumDistinctPlayersCasual'] = $gameData['NumDistinctPlayers'];
$gameData['NumDistinctPlayersHardcore'] = $gameData['NumDistinctPlayers'];

return response()->json($gameData);
