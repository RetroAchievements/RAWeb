<?php

use RA\Permissions;

function PermissionsToString($permissions)
{
    $permissionsStr = ["Spam", "Banned", "Unregistered", "Registered", "Junior Developer", "Developer", "Admin", "Root"];
    return $permissionsStr[$permissions - (Permissions::Spam)]; // Offset of 0
}
