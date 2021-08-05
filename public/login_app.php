<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

//	Auto login from app uses token. Standard login from app uses password.
$user = requestInputPost('u', null);
$pass = requestInputPost('p', null);
$token = requestInputPost('t', null);

$response = [];

$responseCode = login_appWithToken($user, $pass, $token, $scoreOut, $messagesOut);

if ($responseCode == -1) {
    http_response_code(401);
    $response['Success'] = false;
    $response['Error'] = "Automatic login failed (token expired), please login manually!";
} else {
    if ($responseCode == 1) {
        getAccountDetails($user, $userDetails);
        $response['Success'] = true;
        $response['User'] = $user;
        $response['Token'] = $token;
        $response['Score'] = $scoreOut;
        $response['Messages'] = $messagesOut;
        $response['Permissions'] = $userDetails['Permissions'];
        $response['AccountType'] = PermissionsToString($userDetails['Permissions']);
    } else {
        http_response_code(401);
        $response['Success'] = false;
        $response['Error'] = "Invalid User/Password combination. Please try again";
        // error_log("requestlogin failed $user");
    }
}

echo json_encode($response);
