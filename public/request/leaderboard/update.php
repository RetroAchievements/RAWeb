<?php

use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("imtdflo")) {
    echo "FAILED! (POST)";
    exit;
}

$lbID = requestInputPost('i');
$lbMem = requestInputPost('m');
$lbTitle = requestInputPost('t');
$lbDescription = requestInputPost('d');
$lbFormat = requestInputPost('f');
$lbLowerIsBetter = requestInputPost('l');
$lbDisplayOrder = requestInputPost('o');

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    echo "FAILED! Cannot validate $user. Are you a developer?";
    exit;
}

$prevData = GetLeaderboardData($lbID, $user, 1, 0, false);
$prevUpdated = strtotime($prevData["LBUpdated"]);

// Only let jr. devs update their own leaderboards
if ($permissions == Permissions::JuniorDeveloper && $prevData["LBAuthor"] != $user) {
    echo "FAILED!";
    exit;
}

if (submitLBData($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder)) {
    echo "OK";

    $updatedData = GetLeaderboardData($lbID, $user, 2, 0, false);
    $updated = strtotime($updatedData['LBUpdated']);
    $dateDiffMins = ($updated - $prevUpdated) / 60;

    if (!empty($updatedData['Entries'])) {
        if ($dateDiffMins > 10) {
            $commentText = 'made updates to this leaderboard';
            addArticleComment("Server", ArticleType::Leaderboard, $lbID, "\"$user\" $commentText.", $user);
        }
    }
    exit;
}

echo "FAILED!";
exit;
