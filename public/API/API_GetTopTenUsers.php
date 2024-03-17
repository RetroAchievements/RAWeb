<?php

/*
 *  API_GetTopTenUsers - gets information about the top ten users (by score) for the site
 *
 *  array
 *   object     [value]
 *    string     1                     name of the user
 *    int        2                     total points earned by the user
 *    int        3                     total RetroPoints ("white points") earned by the user
 */

$dataOut = getTopUsersByScore(10);

return response()->json($dataOut);
