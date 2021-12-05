<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\Permissions;

if (!ValidatePOSTChars("u")) {
    echo "FAILED";
    header("Location: " . getenv('APP_URL') . "?e=e_baddata");
}

$user = requestInputPost('u');

if (validateUser_cookie($actingUser, null, Permissions::Unregistered)) {
    if ($user !== $actingUser && !validateUser_cookie($actingUser, null, Permissions::Admin)) {
        return false;
    }
    removeAvatar($user);
    if ($user === $actingUser) {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php");
        exit;
    }
}

header("Location: " . getenv('APP_URL') . "/user/" . $user);
