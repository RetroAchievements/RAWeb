<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

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

//	Somewhat elevated privileges to modify an LB:
if (validateFromCookie($user, $points, $permissions, \RA\Permissions::Developer)
    && $source == $user) {
    if (submitLBData($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder)) {
        echo "OK";
        $commentText = 'made updates to this leaderboard';
        addArticleComment("Server", \RA\ArticleType::Leaderboard, $lbID, "\"$user\" $commentText.", $user);
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
