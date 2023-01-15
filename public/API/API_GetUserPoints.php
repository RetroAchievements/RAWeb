<?php

/*
 *  API_GetUserPoints
 *    u : username
 *
 *  int        id                      unique identifier of the user
 *  int        points                  number of hardcore points the user has
 *  int        softcorePoints          number of softcore points the user has
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
    'id' => $userDetails['ID'],
    'points' => $userDetails['RAPoints'],
    'softcorePoints' => $userDetails['RASoftcorePoints']
]));