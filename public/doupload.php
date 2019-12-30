<?php
require_once __DIR__ . '/../lib/bootstrap.php';

//	Syntax:
//	doupload.php?r=uploadbadgeimage&<params> (Web)
//	doupload.php?r=uploadbadgeimage&u=user&t=token&<params> (From App)

$response = ['Success' => true];

//	Global RESERVED vars:
$requestType = seekPOSTorGET('r');
$user = seekPOSTorGET('u');
$token = seekPOSTorGET('t');

$bounceReferrer = seekPOSTorGET('b'); //	TBD: Remove!

$validLogin = false;

if (isset($token)) {
    $validLogin = RA_ReadTokenCredentials($user, $token, $points, $truePoints, $unreadMessageCount, $permissions);
}
if ($validLogin == false) {
    $validLogin = RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);
}

//  Infer from app
if (isset($_FILES["file"]) && isset($_FILES["file"]["name"])) {
    $requestType = substr($_FILES["file"]["name"], 0, -4);
    error_log("RT: " . $requestType);
}
//error_log( "doupload.php" );
//error_log( print_r( $_FILES, true ) );
//	Interrogate requirements:
switch ($requestType) {
    case "uploadbadgeimage":
        $response['Response'] = UploadBadgeImage($_FILES["file"]);
        break;

    case "uploaduserpic":
        $filename = seekPOSTorGET('f');
        $rawImage = seekPOSTorGET('i');
        $response['Response'] = UploadUserPic($user, $filename, $rawImage);
        break;

    default:
        $errorMsg = "Unknown Request: '" . $requestType . "'";
        $response['Success'] = false;
        $response['Error'] = $errorMsg;
        error_log("User: $user, Request$requestType: $errorMsg");
        break;
}

settype($response['Success'], 'boolean');
echo json_encode($response);
