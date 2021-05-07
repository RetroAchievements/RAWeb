<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=error");
    exit;
}

if (deleteRequest($user)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=ok");
    exit;
}
header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=error");
