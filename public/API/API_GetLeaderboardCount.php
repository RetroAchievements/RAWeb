<?php

use App\Models\Game;

/*
 * API_GetLeaderboardCount - Returns the number of leaderboards associated with a game and their details.
 * g : Game ID
 *
 * int        GameID                     Unique identifier of the game.
 * int        LeaderboardCount           Number of leaderboards associated with the game.
 * array      Leaderboards               Details of each leaderboard.
 */

$gameID = (int) request()->query('g');

// Retrieve the game with the specified ID, including its leaderboards
$game = Game::with(['leaderboards' => function ($query) {
    $query->select(['ID', 'GameID', 'Title', 'Description']); // Limit the selected columns for performance
}])->find($gameID);

if (!$game) {
    return response()->json([
        'GameID' => $gameID,
        'LeaderboardCount' => 0,
        'Leaderboards' => [],
    ]);
}

// If there are no leaderboards for this game, return with zero count
if ($game->leaderboards->isEmpty()) {
    return response()->json([
        'GameID' => $game->ID,
        'LeaderboardCount' => 0,
        'Leaderboards' => [],
    ]);
}

// Prepare leaderboard details for the JSON response
$leaderboardDetails = $game->leaderboards->map(function ($leaderboard) {
    return [
        'LeaderboardID' => $leaderboard->ID,
        'Title' => $leaderboard->Title,
        'Description' => $leaderboard->Description,
    ];
});

return response()->json([
    'GameID' => $game->ID,
    'LeaderboardCount' => $game->leaderboards->count(),
    'Leaderboards' => $leaderboardDetails,
]);

