<?php

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

if (validateFromCookie($user, $points, $permissions, \RA\Permissions::Developer)
    && $source == $user) {
    $prevData = GetLeaderboardData($lbID, $user, 1, 0, false);
    $prevUpdated = $prevData["Updated"];

    if (submitLBData($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder)) {
        echo "OK";

        $updatedData = GetLeaderboardData($lbID, $user, 2, 0, false);
        $updated = $updatedData['Updated'];
        $dateDiffMins = ($updated - $prevUpdated) / 60;

        if (!empty($updatedData['Entries'])) {
            if ($dateDiffMins > 10) {
                $commentText = 'made updates to this leaderboard';
                addArticleComment("Server", \RA\ArticleType::Leaderboard, $lbID, "\"$user\" $commentText.", $user);
            }
        }
        exit;
    } else {
        echo "FAILED!";
        exit;
    }
} else {
    //log_email(__FUNCTION__ . " FAILED! Cannot validate $user. Are you a developer?");
    echo "FAILED! Cannot validate $user ($source). Are you a developer?";
    exit;
}
