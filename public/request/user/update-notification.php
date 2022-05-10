<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("pu")) {
    echo "ERROR";
    exit;
}

$prefs = requestInputPost('p');
$userIn = requestInputPost('u');

if (!RA_ValidateCookie($user, $permissions, $userDetails) || $user != $userIn) {
    echo "ERROR2";
    exit;
}

$query = "UPDATE UserAccounts SET websitePrefs='$prefs', Updated=NOW() WHERE User='$user'";

$dbResult = s_mysql_query($query);
if ($dbResult !== false) {
    echo "OK";
} else {
    log_sql_fail();
    echo "ERROR3";
}
