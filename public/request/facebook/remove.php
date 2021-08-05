<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$userInput = requestInputQuery('u');

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions) && ($user == $userInput)) {
    $query = "UPDATE UserAccounts SET fbPrefs='0', fbUser='0', Updated=NOW() WHERE User='$user'";
    //echo $query . "<br>";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        //	TBD: check this manually!?
        header("location: " . getenv('APP_URL') . "/controlpanel.php");
    //echo "Updated Successfully! $user is no longer associated with facebook.";
    } else {
        log_sql_fail();
        // error_log("requestremovefb.php db access failed - update query fail!?");
        echo "Failed, unknown error... please ensure you are logged in.";
    }
} else {
    // error_log("requestremovefb.php failed - cannot verify cookie!?");
    echo "Failed - cannot verify cookie, please ensure you are logged as $userInput first.";
}
