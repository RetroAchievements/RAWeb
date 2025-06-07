<?php

/*
 *  API_GetGameInfoAndUserProgress
 *    g : game id
 *    u : username or user ULID
 *    a : if 1, include highest award metadata (default: 0)
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
 *  int        NumAwardedToUser           number of achievements earned by the user
 *  int        NumAwardedToUserHardcore   number of achievements earned by the user in hardcore
 *  string     UserCompletion             percentage of achievements earned by the user
 *  string     UserCompletionHardcore     percentage of achievements earned by the user in hardcore
 *  map        Achievements
 *   string     [key]                     unique identifier of the achievement
 *    int        ID                       unique identifier of the achievement
 *    string     Title                    title of the achievement
 *    string     Description              description of the achievement
 *    int        Points                   number of points the achievement is worth
 *    int        TrueRatio                number of RetroPoints ("white points") the achievement is worth
 *    string     BadgeName                unique identifier of the badge image for the achievement
 *    string?    Type                     "progression", "win_condition", "missable" or null
 *    int        NumAwarded               number of times the achievement has been awarded
 *    int        NumAwardedHardcore       number of times the achievement has been awarded in hardcore
 *    int        DisplayOrder             field used for determining which order to display the achievements
 *    string     Author                   user who originally created the achievement
 *    string     AuthorULID               queryable stable unique identifier of the user who first created the achievement
 *    datetime   DateCreated              when the achievement was created
 *    datetime   DateModified             when the achievement was last modified
 *    string     MemAddr                  md5 of the logic for the achievement
 *    datetime   DateEarned               when the achievement was earned by the user
 *    datetime   DateEarnedHardcore       when the achievement was earned by the user in hardcore
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
 *  bool       IsFinal                    deprecated, will always be false
 *  string     RichPresencePatch          md5 of the script for generating the rich presence for the game
 *  ?string    HighestAwardKind           "mastered", "completed", "beaten-hardcore", "beaten-softcore", or null. requires the 'a' query param to be 1.
 *  ?datetime  HighestAwardDate           an ISO8601 timestamp string, or null, for when the HighestAwardKind was granted. requires the 'a' query param to be 1.
 */

use App\Actions\FindUserByIdentifierAction;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Platform\Enums\AchievementFlag;
use App\Support\Rules\ValidUserIdentifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'g' => ['required', 'min:1'],
    'u' => ['required', new ValidUserIdentifier()],
]);

$targetUser = (new FindUserByIdentifierAction())->execute($input['u']);
if (!$targetUser) {
    return response()->json([]);
}

$gameId = (int) $input['g'];
$game = Game::where('ID', $gameId)->with('system')->first();
if (!$game) {
    return response()->json([]);
}

$playerGame = $targetUser->playerGames()->where('game_id', $gameId)->first();

$gameData = [
    'ID' => $game->id,
    'Title' => $game->title,
    'ConsoleID' => $game->system->id,
    'ConsoleName' => $game->system->name,
    'ParentGameID' => $game->parentGameId,
    'NumDistinctPlayers' => $game->players_total,
    'NumDistinctPlayersCasual' => $game->players_total,
    'NumDistinctPlayersHardcore' => $game->players_total,
    'NumAchievements' => $game->achievements_published,
    'NumAwardedToUser' => $playerGame->achievements_unlocked ?? 0,
    'NumAwardedToUserHardcore' => $playerGame->achievements_unlocked_hardcore ?? 0,
    'UserCompletion' => sprintf("%01.2f%%", ($playerGame->completion_percentage ?? 0) * 100),
    'UserCompletionHardcore' => sprintf("%01.2f%%", ($playerGame->completion_percentage_hardcore ?? 0) * 100),
    'ForumTopicID' => $game->ForumTopicID,
    'Flags' => 0,
    'ImageIcon' => $game->ImageIcon,
    'ImageTitle' => $game->ImageTitle,
    'ImageIngame' => $game->ImageIngame,
    'ImageBoxArt' => $game->ImageBoxArt,
    'Publisher' => $game->Publisher,
    'Developer' => $game->Developer,
    'Genre' => $game->Genre,
    'Released' => $game->released_at ? $game->released_at->format('Y-m-d') : null,
    'ReleasedAtGranularity' => $game->released_at_granularity,
    'IsFinal' => false,
    'RichPresencePatch' => md5($game->RichPresencePatch),
];

if (!$game->achievements_published) {
    $gameData['Achievements'] = new stdClass(); // issue #484 - force serialization to {}
} else {
    $achievements = [];

    $publishedAchievements = Achievement::query()
        ->where('GameID', $gameId)
        ->where('Flags', AchievementFlag::OfficialCore->value)
        ->with('developer')
        ->orderBy('DisplayOrder')
        ->get();
    foreach ($publishedAchievements as $achievement) {
        $achievements[strval($achievement->ID)] = [
            'ID' => $achievement->ID,
            'Title' => $achievement->Title,
            'Description' => $achievement->Description,
            'Points' => $achievement->Points,
            'TrueRatio' => $achievement->TrueRatio,
            'Type' => $achievement->type,
            'BadgeName' => $achievement->BadgeName,
            'NumAwarded' => $achievement->unlocks_total,
            'NumAwardedHardcore' => $achievement->unlocks_hardcore_total,
            'DisplayOrder' => $achievement->DisplayOrder,
            'Author' => $achievement->developer->display_name,
            'AuthorULID' => $achievement->developer->ulid,
            'DateCreated' => $achievement->DateCreated->format('Y-m-d H:i:s'),
            'DateModified' => $achievement->DateModified->format('Y-m-d H:i:s'),
            'MemAddr' => md5($achievement->MemAddr),
        ];
    }

    $playerAchievements = $targetUser->playerAchievements()->whereIn('achievement_id', array_keys($achievements))->get();
    foreach ($playerAchievements as $playerAchievement) {
        $idStr = strval($playerAchievement->achievement_id);

        $achievements[$idStr]['DateEarned'] = $playerAchievement->unlocked_at->format('Y-m-d H:i:s');
        if ($playerAchievement->unlocked_hardcore_at) {
            $achievements[$idStr]['DateEarnedHardcore'] = $playerAchievement->unlocked_hardcore_at->format('Y-m-d H:i:s');
        }
    }

    $gameData['Achievements'] = $achievements;
}

$includeAwardMetadata = request()->query('a', '0');
if ($includeAwardMetadata == '1') {
    $highestAwardMetadata = PlayerBadge::getHighestUserAwardForGameId($targetUser, $gameId);

    if ($highestAwardMetadata) {
        $gameData['HighestAwardKind'] = $highestAwardMetadata['highestAwardKind'];
        $gameData['HighestAwardDate'] = $highestAwardMetadata['highestAward']->AwardDate->toIso8601String();
    } else {
        $gameData['HighestAwardKind'] = null;
        $gameData['HighestAwardDate'] = null;
    }
}

return response()->json($gameData);
