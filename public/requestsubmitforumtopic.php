<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$forumID = seekPOST( 'f' );
	
	if( !ValidatePOSTChars( "ftp" ) )
	{
		header( "Location: http://" . AT_HOST . "/createtopic.php?f=$forumID&e=invalidparams" );
		exit;
	}

	$topicTitle = seekPOST( 't' );
	$topicPayload = seekPOST( 'p' );
	
	$bannedTitles = array( 
		'kitchen', 
		'stilhaus', 
		'k i t c h e n', 
		'k,i,t,c,h,e,n', 
		'k.i.t.c.h.e.n', 
		'k*i*t*c*h*e*n', 
		'k\'i\'t\'c\'h\'e\'n', 
		'k_i_t_c_h_e_n',
		's,t,i,l,h,a,u,s', 
		's*t*i*l*h*a*u*s', 
		'k itchen',
		'ki tchen',
		'kit chen',
		'kitc hen',
		'kitch en',
		'kitche n'
		);
		
	$nextWord = $bannedTitles[0];
	foreach( $bannedTitles as $nextWord )
	{
		$testTitle = strtolower( $topicTitle );
		if( strpos( $testTitle, $nextWord ) !== false )
		{
			echo "Contains banned word: $nextWord (found in '$topicTitle').<br/>Please try again.";
			exit;
		}
	}
	
    if( validateFromCookie( $user, $points, $permissions, \RA\Permissions::Registered ) )
	{
		if( submitNewTopic( $user, $forumID, $topicTitle, $topicPayload, $topicID ) )
		{
			//	Good!
			header( "Location: http://" . AT_HOST . "/viewtopic.php?t=$topicID" );
			exit;
		}
		else
		{
			error_log( __FILE__ );
			error_log( "Issues2: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload" );
			
			header( "Location: http://" . AT_HOST . "/createtopic.php?e=issuessubmitting" );
			exit;
		}
	}
	else
	{
		error_log( __FILE__ );
		error_log( "Issues: user $user, cookie $cookie, topicID $topicID, payload: $commentPayload" );
		header( "Location: http://" . AT_HOST . "/createtopic.php?e=badcredentials" );
		exit;
	}
	
	exit;
?>
