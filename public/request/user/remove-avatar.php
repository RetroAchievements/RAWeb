<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("u")) {
    echo "FAILED";
    header("Location: " . getenv('APP_URL') . "?e=e_baddata");
}

$user = requestInputPost('u');

if (RA_ValidateCookie($actingUser, $permissions, $actingUserDetails, Permissions::Registered)) {
    if ($user !== $actingUser && $permissions < Permissions::Admin) {
        header("Location: " . getenv('APP_URL') . "/user/" . $user);
        return false;
    }
    removeAvatar($user);
    if ($user === $actingUser) {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php");
        exit;
    }
}

header("Location: " . getenv('APP_URL') . "/user/" . $user);
