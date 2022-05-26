<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("m")) {
    header("Location: " . getenv('APP_URL') . "?e=invalidparams");
    exit;
}

$newMotto = requestInputPost('m');

sanitize_sql_inputs($user, $cookie, $newMotto);

if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    $query = "
			UPDATE UserAccounts
			SET Motto='$newMotto', Updated=NOW()
			WHERE User='$user'";

    global $db;
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        $changeErrorCode = "changeok";
    } else {
        log_sql_fail();
        $changeErrorCode = "changeerror";
    }
} else {
    $changeErrorCode = "changeerror";
}

header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=$changeErrorCode");
