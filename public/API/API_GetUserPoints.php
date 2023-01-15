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

$retVal = [];
getAccountDetails($user, $userDetails);

if (!$userDetails) {
    return response()->json([
        'id' => null,
        'user' => $user,
    ], 404);
}

$retVal['id'] = (int) $userDetails['ID'];
$retVal['points'] = (int) $userDetails['RAPoints'];
$retVal['softcorePoints'] = (int) $userDetails['RASoftcorePoints'];

return response()->json($retVal);
