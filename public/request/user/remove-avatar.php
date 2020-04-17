<?php

use RA\Permissions;

require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("u")) {
    echo "FAILED";
    header("Location: " . getenv('APP_URL') . "?e=e_baddata");
}

$user = seekPOST('u');

if (validateUser_cookie($actingUser, null, Permissions::Admin) === true) {
    $filePath = rtrim(getenv('DOC_ROOT'), '/') . '/public/UserPic/' . $user . '.png';
    if (\file_exists($filePath)) {
        \unlink($filePath);
    }
}

header("Location: " . getenv('APP_URL') . "/user/" . $user);
