<?php

/*
 *  API_GetLeaderboardUsersRanking
 *    lbID : leaderboard id
 *    offset : offset for pagination
 *    limit : number of records to return
 *
 *  array
 *   object     [value]
 *    string     User                   name of user
 *    int        Score                  user's score
 *    datetime   DateSubmitted          when the score was submitted
 *    int        Rank                   rank of the user in the specified leaderboard
 */

$lbID = (int) request()->query('lbID');
$offset = (int) request()->query('offset', '0'); // default to '0' if not provided
$limit = (int) request()->query('limit', '100'); // default to '100' if not provided

$leaderboardData = GetLeaderboardData($lbID, null, $limit, $offset, true);

if (empty($leaderboardData['Entries'])) {
    return response()->json([
        'LeaderboardID' => $lbID,
        'Entries' => [],
    ]);
}

return response()->json([
    'LeaderboardID' => $lbID,
    'Entries' => array_map(function ($entry) {
        return [
            'User' => $entry['User'],
            'Score' => $entry['Score'],
            'DateSubmitted' => date('c', $entry['DateSubmitted']),
            'Rank' => $entry['Rank'],
        ];
    }, $leaderboardData['Entries']),
]);
