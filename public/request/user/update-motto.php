<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!ValidatePOSTChars("ucm")) {
    header("Location: " . getenv('APP_URL') . "?e=invalidparams");
    exit;
}

$user = seekPost('u');
$cookie = seekPost('c');
$newMotto = seekPost('m');

sanitize_query_inputs($user, $cookie, $newMotto);

if (validateUser_cookie($user, $cookie, 1)) {
    $query = "
			UPDATE UserAccounts
			SET Motto='$newMotto', Updated=NOW()
			WHERE User='$user'";

    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        // error_log(__FILE__ . " user $user to $newMotto - associate successful!");
        $changeErrorCode = "changeok";
    } else {
        // error_log(__FILE__);
        log_sql_fail();
        $changeErrorCode = "changeerror";
    }
} else {
    // error_log(__FILE__);
    $changeErrorCode = "changeerror";
}

header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=$changeErrorCode");
