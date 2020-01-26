<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$user = seekGET('u', null);
$gameCSV = seekGET('i', "");

getUserProgress($user, $gameCSV, $data);

echo jsonp_encode($data);
