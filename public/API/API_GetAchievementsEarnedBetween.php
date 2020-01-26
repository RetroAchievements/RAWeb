<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$user = seekGET('u', null);
$unixTimeInputStart = seekGET('f', "");
$unixTimeInputEnd = seekGET('t', "");

$dateStrStartF = date("Y-m-d H:i:s", $unixTimeInputStart);
$dateStrEndF = date("Y-m-d H:i:s", $unixTimeInputEnd);

$data = getAchievementsEarnedBetween($dateStrStartF, $dateStrEndF, $user);

foreach ($data as &$nextData) {
    $nextData['BadgeURL'] = "/Badge/" . $nextData['BadgeName'] . ".png";
    $nextData['GameURL'] = "/Game/" . $nextData['GameID'];
}

echo jsonp_encode($data);
