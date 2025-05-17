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

use App\Models\GameHash;

$consoleID = (int) request()->query('i');
if ($consoleID <= 0) {
    return response()->json(['success' => false]);
}

$withAchievements = (bool) request()->query('f');
$withHashes = (bool) request()->query('h');
$offset = (int) request()->query('o');
$count = (int) request()->query('c');

$query = DB::table('GameData')
    ->leftjoin('Console AS c', 'c.ID', '=', 'GameData.ConsoleID')
    ->leftjoin('Achievements AS ach', 'ach.GameID', '=', 'GameData.ID')
    ->leftjoin('LeaderboardDef AS ld', 'ld.GameID', '=', 'GameData.ID')
    ->select(
        'GameData.*',
        'c.Name as ConsoleName',
        DB::raw('MAX(ach.DateModified) as DateModified'),
        DB::raw('COALESCE(GameData.achievements_published,0) AS NumAchievements'),
        DB::raw('COUNT(DISTINCT ld.ID) AS NumLBs')
    )
    ->where('GameData.ConsoleID', $consoleID)
    ->when($withAchievements, function ($query) {
        return $query->where('GameData.achievements_published', '>', 0);
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
    ->groupBy('GameData.ID')
    ->orderBy('GameData.Title', 'asc');

$queryResponse = $query->get();

$response = [];

foreach ($queryResponse as $game) {
    $responseEntry = [
        'Title' => $game->Title,
        'ID' => $game->ID,
        'ConsoleID' => $game->ConsoleID,
        'ConsoleName' => $game->ConsoleName,
        'ImageIcon' => $game->ImageIcon,
        'NumAchievements' => (int) $game->NumAchievements,
        'NumLeaderboards' => $game->NumLBs ?? 0,
        'Points' => $game->points_total ?? 0,
        'DateModified' => $game->DateModified,
        'ForumTopicID' => $game->ForumTopicID,
    ];

    if ($withHashes) {
        $responseEntry['Hashes'] = [];
    }

    $response[] = $responseEntry;
}

if ($withHashes) {
    $hashes = GameHash::compatible()
        ->select('game_id', 'md5')
        ->whereIn('game_id', array_column($response, 'ID'));
    foreach ($hashes->get() as $hash) {
        foreach ($response as &$entry) {
            if ($entry['ID'] == $hash->game_id) {
                $entry['Hashes'][] = $hash->md5;
                break;
            }
        }
    }
}

return response()->json($response);
