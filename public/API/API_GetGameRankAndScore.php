<?php

/*
 *  API_GetGameRankAndScore - returns Latest Masters or High Scores entries for a game
 *    g : game id
 *    t : type. 1=Latest Masters, 0=High Scores (default: 0)
 *
 *  array
 *   object     [value]
 *    string     User                name of user
 *    string     ULID                queryable stable unique identifier of the user
 *    int        NumAchievements     number of achievements earned by the user for the game
 *    int        TotalScore          number of points earned by the user for the game
 *    datetime   LastAward           when the user's latest achievement for the game was unlocked
 */

use App\Models\Game;
use App\Models\User;
use App\Platform\Services\GameTopAchieversService;
use Carbon\Carbon;

$gameId = (int) request()->query('g');
if ($gameId <= 0) {
    return response()->json(['success' => false]);
}

$game = Game::find($gameId);
if (!$game) {
    return response()->json(['success' => false]);
}

$gameTopAchievers = [];

$service = new GameTopAchieversService();
$service->initialize($game);

[$numMasteries, $topAchievers] = $service->getTopAchieversComponentData();

$type = (int) request()->query('t');
if (($type === 1 && $numMasteries >= 10) || ($type !== 1 && $numMasteries < 10)) {
    // component caches the 10 most recent masteries if there are at least 10 masteries,
    // or the top earners if there are less than 10 masteries.
    foreach ($topAchievers as $playerGame) {
        $gameTopAchievers[] = [
            'User' => $playerGame['user_display_name'],
            'ULID' => $playerGame['user_ulid'],
            'NumAchievements' => $playerGame['achievements_unlocked_hardcore'],
            'TotalScore' => $playerGame['points_hardcore'],
            'LastAward' => Carbon::createFromTimestamp($playerGame['last_unlock_hardcore_at'])->format('Y-m-d H:i:s'),
        ];
    }
} else {
    // cannot use component cached data. run the query.
    $playerGames = ($type === 1) ? $service->recentMasteries() : $service->highestPointEarners();

    foreach ($playerGames as $playerGame) {
        $gameTopAchievers[] = [
            'User' => $playerGame->user->display_name,
            'ULID' => $playerGame->user->ulid,
            'NumAchievements' => $playerGame->achievements_unlocked_hardcore,
            'TotalScore' => $playerGame->points_hardcore,
            'LastAward' => $playerGame->last_unlock_hardcore_at->format('Y-m-d H:i:s'),
        ];
    }
}

return response()->json($gameTopAchievers);
