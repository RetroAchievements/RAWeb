<?php require_once __DIR__ . '/../lib/bootstrap.php';

//	Auto login from app uses token. Standard login from app uses password.
$user = seekPOST('u', null);
$pass = seekPOST('p', null);
$token = seekPOST('t', null);

$response = [];

$errorCode = login_appWithToken($user, $pass, $token, $scoreOut, $messagesOut);
settype($response['Success'], 'boolean');

if ($errorCode == -1) {
    $response['Success'] = false;
    $response['Error'] = "Automatic login failed (token expired), please login manually!\n";
} else {
    if ($errorCode == 1) {
        getAccountDetails($user, $userDetails);
        $response['Success'] = true;
        $response['User'] = $user;
        $response['Token'] = $token;
        $response['Score'] = $scoreOut;
        $response['Messages'] = $messagesOut;
    } else {
        $response['Success'] = false;
        $response['Error'] = "Invalid User/Password combination. Please try again\n";
        error_log("requestlogin failed: $pass, $token, $success");
    }
}

echo json_encode($response);
