<?php

/*
 *  API_GetUserPoints
 *    u : username
 *
 *  int        Points                  number of hardcore points the user has
 *  int        SoftcorePoints          number of softcore points the user has
 */

use App\Models\User;

$username = request()->query('u');

$foundUser = User::whereName($username)->first();

if (!$foundUser) {
    return response()->json([
        'User' => $username,
    ], 404);
}

return response()->json(array_map('intval', [
    'Points' => $foundUser->points,
    'SoftcorePoints' => $foundUser->points_softcore,
]));
