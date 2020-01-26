<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$gameID = seekGET('i');

echo jsonp_encode(getAchievementIDs($gameID));
