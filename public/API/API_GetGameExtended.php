<?php

/*
 *  API_GetGameExtended - returns information about a game
 *    i : game id
 *
 *  int        ID                         unique identifier of the game
 *  string     Title                      name of the game
 *  int        ConsoleID                  unique identifier of the console associated to the game
 *  string     ConsoleName                name of the console associated to the game
 *  int        ParentGameID               unique identifier of the parent game if this is a subset
 *  string     NumDistinctPlayersCasual   number of unique players who have earned achievements for the game
 *  string     NumDistinctPlayersHardcore number of unique players who have earned achievements for the game in hardcore
 *  int        NumAchievements            count of core achievements associated to the game
 *  map        Achievements
 *   string     [key]                     unique identifier of the achievement
 *    string     ID                       unique identifier of the achievement
 *    string     Title                    title of the achievement
 *    string     Description              description of the achievement
 *    string     Points                   number of points the achievement is worth
 *    string     TrueRatio                number of "white" points the achievement is worth
 *    string     BadgeName                unique identifier of the badge image for the achievement
 *    string     NumAwarded               number of times the achievement has been awarded
 *    string     NumAwardedHardcore       number of times the achievement has been awarded in hardcore
 *    string     DisplayOrder             field used for determining which order to display the achievements
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
 *    string    SetType                   set type claimed: 0 - new set, 1 - revision
 *    string    ClaimType                 claim type: 0 - primary, 1 - collaboration
 *    string    Created                   date the claim was made
 *    string    Expiration                date the claim will expire
 */

$gameID = (int) request()->query('i');
getGameMetadata($gameID, null, $achData, $gameData, metrics: true);

foreach ($achData as &$achievement) {
    $achievement['MemAddr'] = md5($achievement['MemAddr'] ?? null);
}
$gameData['Claims'] = getClaimData($gameID, false);
$gameData['Achievements'] = $achData;
$gameData['RichPresencePatch'] = md5($gameData['RichPresencePatch'] ?? null);

return response()->json($gameData);
