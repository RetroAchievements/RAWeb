<?php

/*
 *  API_GetUserPoints
 *    u : username
 *
 *  int        Points                  number of hardcore points the user has
 *  int        SoftcorePoints          number of softcore points the user has
 */

$user = request()->query('u');

getAccountDetails($user, $userDetails);

if (!$userDetails) {
    return response()->json([
        'id' => null,
        'user' => $user,
    ], 404);
}

return response()->json(array_map('intval', [
    'Points' => $userDetails['RAPoints'],
    'SoftcorePoints' => $userDetails['RASoftcorePoints'],
]));
