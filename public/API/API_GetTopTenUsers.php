<?php

/*
 *  API_GetTopTenUsers - gets information about the top ten users (by score) for the site
 *
 *  array
 *   object     [value]
 *    string     1                     name of the user
 *    string     2                     total points earned by the user
 *    string     3                     total "white" points earned by the user
 */

$dataOut = [];
$numFound = getTopUsersByScore(10, $dataOut);

return response()->json($dataOut);
