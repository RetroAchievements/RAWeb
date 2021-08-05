<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("u")) {
    echo "ERROR";
    exit;
}

$user = requestInputPost('u');

getcookie($userIn, $cookie);
if ($user == $userIn && validateUser_cookie($user, $cookie, 0) == false) {
    echo "ERROR2";
    exit;
}

if (getControlPanelUserInfo($user, $userData)) {
    echo json_encode($userData['Played']);
} else {
    echo "ERROR3";
}
