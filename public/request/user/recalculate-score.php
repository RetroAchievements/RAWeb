<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$targetUser = requestInputPost('u');

if (!authenticateFromCookie($actingUser, $permissions, $actingUserDetails, Permissions::Registered)) {
    header("Location: " . getenv('APP_URL') . "?e=badcredentials");
    exit;
}

if (recalculatePlayerPoints($targetUser)) {
    if ($targetUser !== $actingUser && $permissions >= Permissions::Admin) {
        header("Location: " . getenv('APP_URL') . "/user/$targetUser?e=ok");
    } elseif ($targetUser == $actingUser) {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=ok");
    }
    exit;
}

header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=recalc_error");
