<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);
$gameCSV = requestInputQuery('i', "");

getUserProgress($user, $gameCSV, $data);

echo json_encode($data);
