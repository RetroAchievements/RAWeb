<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("cu")) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=baddata");
    exit;
}

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    http_response_code(401);
    echo json_encode(['Success' => false]);
    exit;
}

if ($permissions < Permissions::Registered) {
    http_response_code(401);
    echo json_encode(['Success' => false]);
    exit;
}

$user = requestInputPost('u');

generateAppToken($user, $token);

header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=resetok");
