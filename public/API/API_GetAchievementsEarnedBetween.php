<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);
$unixTimeInputStart = requestInputQuery('f', "");
$unixTimeInputEnd = requestInputQuery('t', "");

$dateStrStartF = date("Y-m-d H:i:s", $unixTimeInputStart);
$dateStrEndF = date("Y-m-d H:i:s", $unixTimeInputEnd);

$data = getAchievementsEarnedBetween($dateStrStartF, $dateStrEndF, $user);

foreach ($data as &$nextData) {
    $nextData['BadgeURL'] = "/Badge/" . $nextData['BadgeName'] . ".png";
    $nextData['GameURL'] = "/Game/" . $nextData['GameID'];
}

echo json_encode($data);
