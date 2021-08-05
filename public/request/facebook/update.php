<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("pu")) {
    echo "ERROR";
    exit;
}

$prefs = requestInputPost('p');
$user = requestInputPost('u');
getcookie($userIn, $cookie);

if ($user == $userIn && validateUser_cookie($user, $cookie, 0) == false) {
    echo "ERROR2";
    exit;
}

$query = "UPDATE UserAccounts SET fbPrefs='$prefs', Updated=NOW() WHERE User='$user'";

$dbResult = s_mysql_query($query);
if ($dbResult !== false) {
    // error_log(__FILE__ . " user $user to FBPrefs: $prefs - associate successful!");
    echo "OK";
} else {
    log_sql_fail();
    // error_log(__FILE__);
    // error_log($query);
    // error_log("issue with change fb?! $user, $prefs");
    echo "ERROR";
}
