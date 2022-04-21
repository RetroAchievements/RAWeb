<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$response = ['Success' => true];

$user = requestInput('u');
$filename = requestInput('f');
$rawImage = requestInput('i');

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Registered)) {
    http_response_code(401);
    echo json_encode(['Success' => false]);
    exit;
}

$uploadResponse = UploadUserPic($user, $filename, $rawImage);
$response['Success'] = $uploadResponse['Success'];
unset($uploadResponse['Success']);
settype($response['Success'], 'boolean');

if ($uploadResponse['Error']) {
    $response['Error'] = $uploadResponse['Error'];
    unset($uploadResponse['Error']);
}

$response['Response'] = $uploadResponse;

echo json_encode($response, JSON_THROW_ON_ERROR);
