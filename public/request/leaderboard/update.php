<?php

use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("uimtdflo")) {
    echo "FAILED! (POST)";
    exit;
}

$source = requestInputPost('u');
$lbID = requestInputPost('i');
$lbMem = requestInputPost('m');
$lbTitle = requestInputPost('t');
$lbDescription = requestInputPost('d');
$lbFormat = requestInputPost('f');
$lbLowerIsBetter = requestInputPost('l');
$lbDisplayOrder = requestInputPost('o');

getCookie($user, $cookie);

if (validateFromCookie($user, $points, $permissions, Permissions::JuniorDeveloper)
    && $source == $user) {
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
    } else {
        echo "FAILED!";
        exit;
    }
} else {
    echo "FAILED! Cannot validate $user ($source). Are you a developer?";
    exit;
}
