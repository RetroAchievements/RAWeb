<?php

/*
 *  API_GetConsoleIDs - returns mapping of known consoles
 *
 *  array
 *   object    [value]
 *    string    ID                  unique identifier of the console
 *    string    Name                name of the console
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$data = getConsoleIDs();

echo json_encode($data, JSON_THROW_ON_ERROR);
