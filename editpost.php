<?php 
	require_once('db.inc.php');

	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
	{
		if( getAccountDetails( $user, $userDetails ) == FALSE )
		{
			//	Immediate redirect if we cannot validate user!	//TBD: pass args?
			header( "Location: http://" . AT_HOST . "?e=accountissue" );
			exit;
		}
	}
	else
	{
		//	Immediate redirect if we cannot validate cookie!	//TBD: pass args?
		header( "Location: http://" . AT_HOST . "?e=notloggedin" );
		exit;
	}
	
	$requestedComment = seekGet( 'c', 0 );
	settype( $$requestedComment, "integer" );
	
	if( getSingleTopicComment( $requestedComment, $commentData ) == FALSE )
	{
		header( "location: http://" . AT_HOST . "/forum.php?e=unknowncomment" );
		exit;
	}
	
	if( getTopicDetails( $commentData['ForumTopicID'], $topicData ) == FALSE )
	{
		header( "location: http://" . AT_HOST . "/forum.php?e=unknownforum2" );
		exit;
	}
	$existingComment = $commentData['Payload'];
	$thisForumTitle = $topicData['Forum'];
	$thisTopicTitle = $topicData['TopicTitle'];
	$thisTopicID 	= $commentData['ForumTopicID'];
	$thisTopicAuthor= $topicData['Author'];
	$thisAuthor		= $commentData['Author'];
	//$thisForumDescription = $topicData['ForumDescription'];
	//$thisCategoryID = $topicData['CategoryID'];
	//$thisCategoryName = $topicData['CategoryName'];
	
	$pageTitle = "Edit post: $thisTopicTitle";
	
	getCookie( $user, $cookieRaw );
	$errorCode = seekGET('e');
	
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
</head>

<body>

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
	<div id="forums" class="both">
		
		<?php
		echo "<div class='navpath'>";
		echo "<b>Edit Post</b>";
		echo "</div>";
		
		echo "<h2 class='longheader'>$pageTitle</h2>";

		echo "<table class='smalltable'>";
		echo "<tbody>";
		
		echo "<form action='requestsubmiteditpost.php' method='post'>";
		echo "<input type='hidden' value='$cookieRaw' name='c'></input>";
		echo "<input type='hidden' value='$requestedComment' name='i'></input>";
		echo "<input type='hidden' value='$thisTopicID' name='t'></input>";
		echo "<input type='hidden' value='$user' name='u'></input>";
		//echo "<input type='hidden' value='$requestedForumID' name='f'></input>";
		echo "<tr>" . 			  "<td>Forum:</td><td><input type='text' readonly value='$thisForumTitle'></input></td></tr>";
		echo "<tr class='alt'>" . "<td>Topic:</td><td><input type='text' readonly class='fullwidth' value='$thisTopicTitle'></input></td></tr>";
		echo "<tr>" . 			  "<td>Author:</td><td><input type='text' readonly value='$thisAuthor'></input></td></tr>";
		echo "<tr class='alt'>" . "<td>Message:</td><td>";
		
		RenderPHPBBIcons();
		
		echo "<textarea id='commentTextarea' class='fullwidth forum' style='height:300px' rows='32' cols='32' name='p'>$existingComment</textarea></td></tr>";
		echo "<tr>" . 			  "<td></td><td class='fullwidth'><input type='submit' value='Submit post' SIZE='37'/>&nbsp;<a href='/viewtopic.php?t=$thisTopicID&c=$requestedComment'>Cancel</a></td></tr>";
		echo "</form>";
		
		echo "</tbody>";
		echo "</table>";
		
		?>
		
		<br/>
	</div> 
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

