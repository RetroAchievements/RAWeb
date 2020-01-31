<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$user = seekGET('u', null);
$gameCSV = seekGET('i', "");

getUserProgress($user, $gameCSV, $data);

echo json_encode($data);
