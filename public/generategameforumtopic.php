<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( !ValidateGETChars( "ucg" ) )
	{
		header( "Location: http://" . AT_HOST . "/forum.php?e=invalidparams" );
		exit;
	}

	$user = seekGET( 'u' );
	$cookie = seekGET( 'c' );
	$gameID = seekGET( 'g' );
	
	if( validateUser_cookie( $user, $cookie, 1 ) )
	{
		if( generateGameForumTopic( $user, $gameID, $forumTopicID ) )
		{
			//	Good!
			header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$forumTopicID" );
			exit;
		}
		else
		{
			//error_log( __FILE__ );
			error_log( "Issues2: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload" );
			
			header( "Location: http://" . AT_HOST . "/forum.php?e=issuessubmitting" );
			exit;
		}
	}
	else
	{
		//error_log( __FILE__ );
		error_log( "Issues: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload" );
		header( "Location: http://" . AT_HOST . "/forum.php?e=badcredentials" );
		exit;
	}
	
	exit;
?>
