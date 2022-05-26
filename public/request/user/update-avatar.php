<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$response = ['Success' => true];

$user = requestInput('u');
$filename = requestInput('f');
$rawImage = requestInput('i');

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    http_response_code(401);
    echo json_encode(['Success' => false]);
    exit;
}

$response = UploadUserPic($user, $filename, $rawImage);

echo json_encode($response, JSON_THROW_ON_ERROR);
