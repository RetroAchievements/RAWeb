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

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$dataOut = [];
$numFound = getTopUsersByScore(10, $dataOut, null);

echo json_encode($dataOut, JSON_THROW_ON_ERROR);
