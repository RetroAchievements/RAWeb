<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    header("Location: " . getenv('APP_URL') . "?e=badcredentials");
    exit;
}

if (recalculatePlayerPoints($user)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=recalc_ok");
    exit;
}

header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=recalc_error");
