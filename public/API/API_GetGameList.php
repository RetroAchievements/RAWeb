<?php

/*
 *  API_GetGameList - returns games for the specified console
 *    i : console id
 *    f : 1=only return games where NumAchievements > 0 (default: 0)
 *    h : 1=also return hashes (default: 0)
 *    o : offset (optional)
 *    c : count (optional)
 *
 *  array
 *   object     [value]
 *    int        ID                unique identifier of the game
 *    string     Title             title of the game
 *    int        ConsoleID         unique identifier of the console
 *    string     ConsoleName       name of the console
 *    string     ImageIcon         site-relative path to the game's icon image
 *    int        NumAchievements   number of core achievements for the game
 *    int        NumLeaderboards   number of leaderboards for the game
 *    int        Points            total number of points the game's achievements are worth
 *    datetime   DateModified      when the last modification was made
 *                                 NOTE: this only tracks modifications to the achievements of the game,
 *                                       but is consistent with the data reported in the site game list.
 *    ?int       ForumTopicID      unique identifier of the official forum topic for the game
 *    array      Hashes
 *     string     [value]          RetroAchievements hash associated to the game
 */

use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\Leaderboard;
use Illuminate\Support\Facades\DB;

$consoleID = (int) request()->query('i');
if ($consoleID <= 0) {
    return response()->json(['success' => false]);
}

$withAchievements = (bool) request()->query('f');
$withHashes = (bool) request()->query('h');
$offset = (int) request()->query('o');
$count = (int) request()->query('c');

// Build subqueries for aggregated data to avoid an expensive GROUP BY on joined data.
$gameIdsSubquery = Game::query()
    ->select('id')
    ->where('system_id', $consoleID)
    ->when($withAchievements, function ($query) {
        return $query->where('achievements_published', '>', 0);
    });

$achievementsSubquery = Achievement::query()
    ->selectRaw('GameID, MAX(DateModified) as DateModified')
    ->whereIn('GameID', $gameIdsSubquery)
    ->groupBy('GameID');

$leaderboardsSubquery = Leaderboard::query()
    ->selectRaw('GameID, COUNT(*) as NumLBs')
    ->whereIn('GameID', $gameIdsSubquery)
    ->groupBy('GameID');

$query = DB::table('games')
    ->leftJoin('Console AS c', 'c.ID', '=', 'games.system_id')
    ->leftJoinSub($achievementsSubquery, 'ach_data', function ($join) {
        $join->on('ach_data.GameID', '=', 'games.id');
    })
    ->leftJoinSub($leaderboardsSubquery, 'lb_data', function ($join) {
        $join->on('lb_data.GameID', '=', 'games.id');
    })
    ->select(
        'games.*',
        'c.Name as ConsoleName',
        DB::raw('COALESCE(games.achievements_published, 0) AS NumAchievements'),
        DB::raw('COALESCE(ach_data.DateModified, NULL) AS DateModified'),
        DB::raw('COALESCE(lb_data.NumLBs, 0) AS NumLBs')
    )
    ->where('games.system_id', $consoleID)
    ->when($withAchievements, function ($query) {
        return $query->where('games.achievements_published', '>', 0);
    })
    ->when($offset > 0, function ($query) use ($offset) {
        return $query->offset($offset);
    })
    ->when($count > 0, function ($query) use ($count) {
        return $query->limit($count);
    })
    ->when($count == 0, function ($query) {
        return $query->limit(9999999);
    })
    ->orderBy('games.title', 'asc');

$queryResponse = $query->get();

$response = [];

foreach ($queryResponse as $game) {
    $responseEntry = [
        'Title' => $game->title,
        'ID' => $game->id,
        'ConsoleID' => $game->system_id,
        'ConsoleName' => $game->ConsoleName,
        'ImageIcon' => $game->image_icon_asset_path,
        'NumAchievements' => (int) $game->NumAchievements,
        'NumLeaderboards' => $game->NumLBs ?? 0,
        'Points' => $game->points_total ?? 0,
        'DateModified' => $game->DateModified,
        'ForumTopicID' => $game->forum_topic_id,
    ];

    if ($withHashes) {
        $responseEntry['Hashes'] = [];
    }

    $response[] = $responseEntry;
}

if ($withHashes) {
    $responseIndex = [];
    foreach ($response as $index => $entry) {
        $responseIndex[$entry['ID']] = $index;
    }

    $hashes = GameHash::compatible()
        ->select('game_id', 'md5')
        ->whereIn('game_id', array_keys($responseIndex))
        ->orderBy('game_id');

    foreach ($hashes->get() as $hash) {
        $response[$responseIndex[$hash->game_id]]['Hashes'][] = $hash->md5;
    }
}

return response()->json($response);
