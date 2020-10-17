<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$user = requestInputQuery('u', null);
$dateInput = requestInputQuery('d', "");

$data = getAchievementsEarnedOnDay(strtotime($dateInput), $user);

foreach ($data as &$nextData) {
    $nextData['BadgeURL'] = "/Badge/" . $nextData['BadgeName'] . ".png";
    $nextData['GameURL'] = "/Game/" . $nextData['GameID'];
}

echo json_encode($data);
