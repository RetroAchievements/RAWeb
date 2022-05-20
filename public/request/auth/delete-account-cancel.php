<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=error");
    exit;
}

if (cancelDeleteRequest($user)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=ok");
    exit;
}
header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=error");
