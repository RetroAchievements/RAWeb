<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidatePOSTChars("ucm")) {
    header("Location: " . getenv('APP_URL') . "?e=invalidparams");
    exit;
}

$user = seekPost('u');
$cookie = seekPost('c');
$newMotto = mysqli_real_escape_string($db, seekPost('m'));

if (validateUser_cookie($user, $cookie, 1)) {
    $query = "
			UPDATE UserAccounts
			SET Motto='$newMotto', Updated=NOW()
			WHERE User='$user'";

    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        error_log($query);
        error_log(__FILE__ . " user $user to $newMotto - associate successful!");
        $changeErrorCode = "changeok";
    } else {
        error_log(__FILE__);
        error_log($query);
        $changeErrorCode = "changeerror";
    }
} else {
    error_log(__FILE__);
    error_log($query);
    $changeErrorCode = "changeerror";
}

header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=$changeErrorCode");
