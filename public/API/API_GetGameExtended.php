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
 *  string?    Released                   a YYYY-MM-DD date of the game's earliest release date, or null. also see ReleasedAtGranularity.
 *  string?    ReleasedAtGranularity      how precise the Released value is. possible values are "day", "month", "year", and null.
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

use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Platform\Enums\AchievementFlag;
use Carbon\Carbon;

$gameId = (int) request()->query('i');
$flag = (int) request()->query('f', (string) AchievementFlag::OfficialCore);

$game = Game::with('system')->find($gameId);

if (!$game) {
    return response()->json();
}

$gameAchievementSetClaims = AchievementSetClaim::with('user')->where('game_id', $gameId)->get();
$gameAchievements = Achievement::where('GameID', $gameId)->where('Flags', $flag)->findMany($game->achievements);

$gameData = [
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
    'Released' => $game->released_at?->format('Y-m-d'),
    'ReleasedAtGranularity' => $game->released_at_granularity?->value,
    'IsFinal' => $game->IsFinal,
    'RichPresencePatch' => md5($game->RichPresencePatch),
    'GuideURL' => $game->GuideURL,
    'Updated' => $game->Updated->format('Y-m-d\TH:i:s.u\Z'),
];

// Use maps to structure the data with how legacy API consumers might expect it to be returned.
if (!$gameAchievements->isEmpty()) {
    $gameAchievements->loadMissing('developer');

    $gameListAchievements = $gameAchievements->keyBy('ID')->map(function ($am) {
        return [
            'ID' => $am->ID,
            'NumAwarded' => $am->unlocks_total,
            'NumAwardedHardcore' => $am->unlocks_hardcore_total,
            'Title' => $am->Title,
            'Description' => $am->Description,
            'Points' => $am->Points,
            'TrueRatio' => $am->TrueRatio,
            'Author' => $am->developer?->display_name,
            'DateModified' => Carbon::parse($am->DateModified)->format('Y-m-d H:i:s'),
            'DateCreated' => Carbon::parse($am->DateCreated)->format('Y-m-d H:i:s'),
            'BadgeName' => $am->BadgeName,
            'DisplayOrder' => $am->DisplayOrder,
            'MemAddr' => md5($am->MemAddr),
            'type' => $am->type,
        ];
    });
} else {
    $gameListAchievements = new ArrayObject();
}

if (!$gameAchievementSetClaims) {
    $gameClaims = [];
} else {
    $gameClaims = $gameAchievementSetClaims->map(function ($gc) {
        return [
            'User' => $gc->user->display_name,
            'SetType' => $gc->SetType,
            'GameID' => $gc->game_id,
            'ClaimType' => $gc->ClaimType,
            'Created' => Carbon::parse($gc->Created)->format('Y-m-d H:i:s'),
            'Expiration' => Carbon::parse($gc->Finished)->format('Y-m-d H:i:s'),
        ];
    });
}

return response()->json(array_merge(
    $gameData,
    [
        'ConsoleName' => $game->system->Name,
        'ParentGameID' => $game->getParentGame()?->id,
        'NumDistinctPlayers' => $game->players_total,
        'NumAchievements' => count($gameAchievements),
        'Achievements' => $gameListAchievements,
        'Claims' => $gameClaims,
        'NumDistinctPlayersCasual' => $game->players_total, // Deprecated - Only here to maintain API V1 compat
        'NumDistinctPlayersHardcore' => $game->players_total, // Deprecated - Only here to maintain API V1 compat
    ]
));
