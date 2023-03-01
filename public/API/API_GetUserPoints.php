<?php

/*
 *  API_GetUserPoints
 *    u : username
 *
 *  int        Points                  number of hardcore points the user has
 *  int        SoftcorePoints          number of softcore points the user has
 */

$user = request()->query('u');

if (!getPlayerPoints($user, $userDetails)) {
    return response()->json([
        'User' => $user,
    ], 404);
}

return response()->json(array_map('intval', [
    'Points' => $userDetails['RAPoints'],
    'SoftcorePoints' => $userDetails['RASoftcorePoints'],
]));
