<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$response = ['Success' => true];

$user = requestInput('u');
$filename = requestInput('f');
$rawImage = requestInput('i');

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    http_response_code(401);
    echo json_encode(['Success' => false]);
    exit;
}

$response['Response'] = UploadUserPic($user, $filename, $rawImage);

settype($response['Success'], 'boolean');
echo json_encode($response);
