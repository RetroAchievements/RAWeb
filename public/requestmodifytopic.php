<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( !ValidatePOSTChars( "tfv" ) )
	{
		echo "FAILED";
		return;
	}
	
	$topicID = seekPOST('t');
	$field = seekPOST('f');
	$value = seekPOST('v');
	
	error_log( "requestModifyTopic, " . $field . ", " . "$value");
	
	if( validateFromCookie( $user, $unused, $permissions, Permissions::Registered ) )
	{
		if( requestModifyTopic( $user, $permissions, $topicID, $field, $value ) )
		{
			if( $field == ModifyTopicField::DeleteTopic )
				header( "location: http://" . AT_HOST . "/forum.php?e=delete_ok" );
			else
				header( "location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&e=modify_ok" );
			exit;
		}
		else
		{
			header( "location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&e=errors_in_modify" );
			exit;
		}
	}
?>
