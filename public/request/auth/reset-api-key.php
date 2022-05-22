<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    http_response_code(401);
    echo json_encode(['Success' => false]);
    exit;
}

generateAPIKey($user);

header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=resetok");
