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
 *    string     AuthorULID               queryable stable unique identifier of the user who first created the achievement
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
 *  bool       IsFinal                    deprecated, will always be false
 *  string     RichPresencePatch          md5 of the script for generating the rich presence for the game
 *  array      Claims
 *   object    [value]
 *    string    User                      user holding the claim
 *    string    ULID                      queryable stable unique identifier of the user holding the claim
 *    int       SetType                   set type claimed: 0 - new set, 1 - revision
 *    int       ClaimType                 claim type: 0 - primary, 1 - collaboration
 *    string    Created                   date the claim was made
 *    string    Expiration                date the claim will expire
 */

use App\Models\Achievement;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'i' => ['required', 'integer', 'min:1'],
    'f' => [
        'nullable',
        Rule::in([(string) Achievement::FLAG_PROMOTED, (string) Achievement::FLAG_UNPROMOTED]),
    ],
], [
    'f.in' => 'Invalid flag parameter. Valid values are ' . Achievement::FLAG_PROMOTED . ' (published) or ' . Achievement::FLAG_UNPROMOTED . ' (unpublished).',
]);

$gameId = (int) $input['i'];
$isPromoted = Achievement::isPromotedFromLegacyFlags((int) ($input['f'] ?? (string) Achievement::FLAG_PROMOTED));

$game = Game::with('system')->find($gameId);

if (!$game) {
    return response()->json();
}

$gameAchievementSetClaims = AchievementSetClaim::with('user')->where('game_id', $gameId)->get();
$gameAchievements = Achievement::where('game_id', $gameId)->where('is_promoted', $isPromoted)->findMany($game->achievements);

$gameData = [
    'ID' => $game->id,
    'Title' => $game->title,
    'ConsoleID' => $game->system_id,
    'ForumTopicID' => $game->forum_topic_id,
    'Flags' => null, // Always '0', this is different in the extended endpoint test for some reason
    'ImageIcon' => $game->image_icon_asset_path,
    'ImageTitle' => $game->image_title_asset_path,
    'ImageIngame' => $game->image_ingame_asset_path,
    'ImageBoxArt' => $game->image_box_art_asset_path,
    'Publisher' => $game->publisher,
    'Developer' => $game->developer,
    'Genre' => $game->genre,
    'Released' => $game->released_at?->format('Y-m-d'),
    'ReleasedAtGranularity' => $game->released_at_granularity?->value,
    'IsFinal' => false,
    'RichPresencePatch' => md5($game->trigger_definition ?? ''),
    'GuideURL' => $game->legacy_guide_url,
    'Updated' => $game->updated_at->format('Y-m-d\TH:i:s.u\Z'),
];

// Use maps to structure the data with how legacy API consumers might expect it to be returned.
if (!$gameAchievements->isEmpty()) {
    $gameAchievements->loadMissing('developer');

    $gameListAchievements = $gameAchievements->keyBy('id')->map(function ($am) {
        return [
            'ID' => $am->id,
            'NumAwarded' => $am->unlocks_total,
            'NumAwardedHardcore' => $am->unlocks_hardcore,
            'Title' => $am->title,
            'Description' => $am->description,
            'Points' => $am->points,
            'TrueRatio' => $am->points_weighted,
            'Author' => $am->developer?->display_name,
            'AuthorULID' => $am->developer?->ulid,
            'DateModified' => $am->modified_at->format('Y-m-d H:i:s'),
            'DateCreated' => $am->created_at->format('Y-m-d H:i:s'),
            'BadgeName' => $am->image_name,
            'DisplayOrder' => $am->order_column,
            'MemAddr' => md5($am->trigger_definition),
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
            'ULID' => $gc->user->ulid,
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
        'ConsoleName' => $game->system->name,
        'ParentGameID' => $game->parentGameId,
        'NumDistinctPlayers' => $game->players_total,
        'NumAchievements' => count($gameAchievements),
        'Achievements' => $gameListAchievements,
        'Claims' => $gameClaims,
        'NumDistinctPlayersCasual' => $game->players_total, // Deprecated - Only here to maintain API V1 compat
        'NumDistinctPlayersHardcore' => $game->players_total, // Deprecated - Only here to maintain API V1 compat
    ]
));
