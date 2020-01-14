<?php

require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("uimtdflo")) {
    echo "FAILED! (POST)";
    exit;
}

$source = seekPOST('u');
$lbID = seekPOST('i');
$lbMem = seekPOST('m');
$lbTitle = seekPOST('t');
$lbDescription = seekPOST('d');
$lbFormat = seekPOST('f');
$lbLowerIsBetter = seekPOST('l');
$lbDisplayOrder = seekPOST('o');

getCookie($user, $cookie);

//	Somewhat elevated privileges to modify an LB:
if (validateFromCookie($user, $points, $permissions, \RA\Permissions::Developer)
    && $source == $user) {
    if (submitLBData($user, $lbID, $lbMem, $lbTitle, $lbDescription, $lbFormat, $lbLowerIsBetter, $lbDisplayOrder)) {
        echo "OK";
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
