<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$user = requestInputPost('u', null);
$fbUserID = requestInputPost('f', null);

if (!isset($fbUserID)) {
    global $fbConn;
    $fbUserID = $fbConn->getUser();
}

// error_log("requestassociatefb.php called");

try {
    //$fullCookie = $_COOKIE["RAPrefs"];

    // if( $fbUserID == 0 )
    // {
    // echo "FB not found!";
    // error_log( "requestassociatefb.php - error2" );
    // }
    // else if( $fullCookie == NULL )
    // {
    // echo "Cookie not found!";
    // error_log( "requestassociatefb.php - error3" );
    // }
    // else
    if (isset($user) && isset($fbUserID)) {
        if (associateFB($user, $fbUserID)) {
            //	Great
            // error_log("requestassociatefb.php - associate successful ($user, $fbUserID)");
            echo "OK";
        } else {
            // error_log("requestassociatefb.php - error1 ($user, $fbUserID)");
            echo "FAILED1";
        }
    }
} catch (FacebookApiException $e) {
    echo "FAILED2";
    echo $e->getMessage() . "<br>";
    //	Not logged in?
    error_log("FB error: " . $e->getType());
    error_log("FB error: " . $e->getMessage());
}
