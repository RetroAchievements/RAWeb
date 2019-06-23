<?php
function PermissionsToString( $permissions )
{
	$permissionsStr = [ "Spam", "Banned", "Unregistered", "Registered", "Jr. Developer", "Developer", "Admin", "Root" ];
	return $permissionsStr[$permissions - ( \RA\Permissions::Spam )]; //	Offset of 0
}
