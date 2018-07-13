<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Registered ) )
	{
		if( getAccountDetails( $user, $userDetails ) == FALSE )
		{
			//	Immediate redirect if we cannot validate user!	//TBD: pass args?
			header( "Location: " . APP_URL . "?e=accountissue" );
			exit;
		}
	}
	else
	{
		//	Immediate redirect if we cannot validate cookie!	//TBD: pass args?
		header( "Location: " . APP_URL . "?e=notloggedin" );
		exit;
	}
	
	$requestedForumID = seekGet( 'f', 0 );
	settype( $requestedForumID, "integer" );
	
	if( $requestedForumID == 0 )
	{
		header( "location: " . APP_URL . "/forum.php?e=unknownforum" );
		exit;
	}
	
	if( getForumDetails( $requestedForumID, $forumData ) == FALSE )
	{
		header( "location: " . APP_URL . "/forum.php?e=unknownforum2" );
		exit;
	}
	
	//var_dump( $forumData );
	$thisForumID = $forumData['ID'];
	$thisForumTitle = $forumData['ForumTitle'];
	$thisForumDescription = $forumData['ForumDescription'];
	$thisCategoryID = $forumData['CategoryID'];
	$thisCategoryName = $forumData['CategoryName'];
	
	$pageTitle = "Create topic: $thisForumTitle";
	
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
	<?php RenderRecentForumPostsComponent( 4 ); ?>
	<div id="forums" class="left">
		
		<?php
		echo "<div class='navpath'>";
		echo "<a href='forum.php'>Forum Index</a>";
		echo " &raquo; <a href='/forum.php?c=$thisCategoryID'>$thisCategoryName</a>";
		echo " &raquo; <a href='/viewforum.php?f=$thisForumID'>$thisForumTitle</a>";
		echo " &raquo; <b>Create Topic</b></a>";
		echo "</div>";
		
		echo "<h2 class='longheader'>Create Topic: $thisForumTitle</h2>";

		echo "<table class='smalltable'>";
		echo "<tbody>";
		
		echo "<form action='requestsubmitforumtopic.php' method='post'>";
		echo "<input type='hidden' value='$cookieRaw' name='c'></input>";
		echo "<input type='hidden' value='$requestedForumID' name='f'></input>";
		echo "<tr>" . 			  "<td>Forum:</td><td><input type='text' readonly value='$thisForumTitle'></input></td></tr>";
		echo "<tr class='alt'>" . "<td>Author:</td><td><input type='text' readonly value='$user' name='u'></input></td></tr>";
		echo "<tr>" . 			  "<td>Title:</td><td><input class='fullwidth' type='text' value='' name='t'></input></td></tr>";
		echo "<tr class='alt'>" . "<td>Message:</td><td>";
		
		RenderPHPBBIcons();
		
		echo "<textarea id='commentTextarea' class='fullwidth forum' style='height:160px' rows=5 cols=63 name='p'></textarea></td></tr>";
		echo "<tr>" . 			  "<td></td><td class='fullwidth'><input type='submit' value='Submit new topic' SIZE='37'/></td></tr>";
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

