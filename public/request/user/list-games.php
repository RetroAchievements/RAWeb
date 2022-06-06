<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    echo "ERROR2";
    exit;
}

if (getControlPanelUserInfo($user, $userData)) {
    echo json_encode($userData['Played'], JSON_THROW_ON_ERROR);
} else {
    echo "ERROR3";
}
