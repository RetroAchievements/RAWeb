<?php
function PermissionsToString( $permissions )
{
	$permissionsStr = [ "Spam", "Banned", "Unregistered", "Registered", "Super User", "Developer", "Admin", "Root" ];
	return $permissionsStr[$permissions - ( Permissions::Spam )]; //	Offset of 0
}
