<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=error");
    exit;
}

if (cancelDeleteRequest($user)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=ok");
    exit;
}
header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=error");
