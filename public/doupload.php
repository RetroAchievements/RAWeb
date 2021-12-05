<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$response = ['Success' => true];

$requestType = requestInput('r');
$user = requestInput('u');
$token = requestInput('t');

$bounceReferrer = requestInput('b'); //	TBD: Remove!

if (!RA_ReadTokenCredentials($user, $token, $points, $truePoints, $unreadMessageCount, $permissions)) {
    http_response_code(401);
    echo json_encode(['Success' => false]);
    exit;
}

// Infer request type from app
// TODO: remove if not required anymore
if (isset($_FILES["file"]) && isset($_FILES["file"]["name"])) {
    $requestType = mb_substr($_FILES["file"]["name"], 0, -4);
}

switch ($requestType) {
    case "uploadbadgeimage":
        $response['Response'] = UploadBadgeImage($_FILES["file"]);
        break;

    default:
        $errorMsg = "Unknown Request: '" . $requestType . "'";
        $response['Success'] = false;
        $response['Error'] = $errorMsg;
        break;
}

settype($response['Success'], 'boolean');
echo json_encode($response);
