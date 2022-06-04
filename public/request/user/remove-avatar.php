<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$targetUser = requestInputPost('u');

if (!authenticateFromCookie($actingUser, $permissions, $actingUserDetails, Permissions::Registered)) {
    redirect(back());
    exit;
}

if ($targetUser && $targetUser !== $actingUser && $permissions < Permissions::Admin) {
    redirect(back());
    exit;
}

removeAvatar($targetUser);

redirect(back());
