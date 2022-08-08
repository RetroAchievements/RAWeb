<?php

use RA\AchievementType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!ValidatePOSTChars("atdp")) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bad request: parameters missing']);
    exit;
}

$achievementId = (int) requestInputPost('a');
$title = requestInputPost('t');
$description = requestInputPost('d');
$points = requestInputPost('p', null, 'integer');

$achievement = GetAchievementData($achievementId);
if (!$achievement) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Bad Request']);
    exit;
}

// Only allow jr. devs to update base data if they are the sole author of the set
if ($permissions == Permissions::JuniorDeveloper && ((int) $achievement['Flags'] !== AchievementType::Unofficial || !checkIfSoleDeveloper($user, (int) $achievement['GameId']))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

if (UploadNewAchievement(
    author: $achievement['Author'],
    gameID: $achievement['GameID'],
    title: $title,
    desc: $description,
    progress: $achievement['Progress'],
    progressMax: $achievement['ProgressMax'],
    progressFmt: $achievement['ProgressFormat'],
    points: $points,
    mem: $achievement['MemAddr'],
    type: $achievement['Flags'],
    idInOut: $achievement['ID'],
    badge: $achievement['BadgeName'],
    errorOut: $errorOut
)) {
    echo json_encode(['success' => true, 'message' => 'OK']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Something went wrong']);
exit;
