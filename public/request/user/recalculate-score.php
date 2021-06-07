<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\Permissions;

if (!ValidatePOSTChars("u")) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=invalidparams");
    exit;
}

$userIn = requestInputPost('u');

$permOk = RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)
          && ($user == $userIn
              || $permissions >= Permissions::Admin);
if (!$permOk) {
    header("Location: " . getenv('APP_URL') . "?e=badcredentials");
    exit;
}

if (recalcScore($userIn)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=recalc_ok");
    exit;
} else {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=recalc_error");
    exit;
}
