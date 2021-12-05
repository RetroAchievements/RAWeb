<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);
$gameCSV = requestInputQuery('i', "");

getUserProgress($user, $gameCSV, $data);

echo json_encode($data);
