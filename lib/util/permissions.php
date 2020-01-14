<?php

function PermissionsToString($permissions)
{
    $permissionsStr = ["Spam", "Banned", "Unregistered", "Registered", "Super User", "Developer", "Admin", "Root"];
    return $permissionsStr[$permissions - (\RA\Permissions::Spam)]; //	Offset of 0
}
