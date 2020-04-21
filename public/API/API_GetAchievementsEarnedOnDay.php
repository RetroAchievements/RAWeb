<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$user = seekGET('u', null);
$dateInput = seekGET('d', "");

$data = getAchievementsEarnedOnDay(strtotime($dateInput), $user);

foreach ($data as &$nextData) {
    $nextData['BadgeURL'] = "/Badge/" . $nextData['BadgeName'] . ".png";
    $nextData['GameURL'] = "/Game/" . $nextData['GameID'];
}

echo json_encode($data);
