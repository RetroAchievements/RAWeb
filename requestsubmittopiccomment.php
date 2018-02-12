<?php 
	require_once('db.inc.php');
	
	if( !ValidatePOSTChars( "tp" ) )
	{
		header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&e=invalidparams" );
		exit;
	}

	$userIn = seekPOST( 'u' );
	$topicID = seekPOST( 't' );
	$commentPayload = seekPOST( 'p' );
	
	if( validateFromCookie( $user, $unused, $permissions, Permissions::Registered ) )
	{
		if( submitTopicComment( $user, $topicID, $commentPayload, $newCommentID ) )
		{
			//	Good!
			header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&c=$newCommentID" );
			exit;
		}
		else
		{
			error_log( __FILE__ );
			error_log( "Issues2: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload" );
			
			header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&e=issuessubmitting" );
			exit;
		}
	}
	else
	{
		error_log( __FILE__ );
		error_log( "Issues: userin $userIn, topicID $topicID, payload: $commentPayload" );
		log_email( "Issues: userin $userIn, topicID $topicID, payload: $commentPayload" );
		header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&e=badcredentials" );
		exit;
	}
	
	exit;
?>