<?php 
	require_once('db.inc.php');
	
	if( !ValidatePOSTChars( "uctpi" ) )
	{
		header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&e=invalidparams" );
		exit;
	}

	$user = seekPOST( 'u' );
	$cookie = seekPOST( 'c' );
	$topicID = seekPOST( 't' );
	$commentPayload = seekPOST( 'p' );
	$commentID = seekPOST( 'i' );
	
	if( validateUser_cookie( $user, $cookie, 1 ) )
	{
		if( editTopicComment( $commentID, $commentPayload ) )
		{
			//	Good!
			//error_log( "HOST: " );
			//error_log( AT_HOST );
			header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&c=$commentID" );
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
		error_log( "Issues: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload" );
		header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$topicID&e=badcredentials" );
		exit;
	}
	
	exit;
?>