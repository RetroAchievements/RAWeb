<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	
	$requestedForumID = seekGET( 'f', NULL );
	$offset = seekGET( 'o', 0 );
	$count = seekGET( 'c', 25 );
	
	settype( $requestedForumID, "integer" );
	settype( $offset, "integer" );
	settype( $count, "integer" );
	
	$numUnofficialLinks = 0;
	if( $permissions >= \RA\Permissions::Admin )
	{
		$unofficialLinks = getUnauthorisedForumLinks();
		$numUnofficialLinks = count( $unofficialLinks );
	}

	if( $requestedForumID == 0 )
	{
		if( $permissions >= \RA\Permissions::Admin )
		{
			//	Continue
			$viewingUnauthorisedForumLinks = true;
		}
		else
		{
			header( "location: " . getenv('APP_URL') . "/forum.php?e=unknownforum" );
			exit;
		}
		
		$thisForumID = 0;
		$thisForumTitle = "Unauthorised Links";
		$thisForumDescription = "Unauthorised Links";
		$thisCategoryID = 0;
		$thisCategoryName = "Unauthorised Links";
	
		$topicList = getUnauthorisedForumLinks();
	
		$requestedForum = "Unauthorised Links";
	}
	else
	{
		if( getForumDetails( $requestedForumID, $forumDataOut ) == FALSE )
		{
			header( "location: " . getenv('APP_URL') . "/forum.php?e=unknownforum2" );
			exit;
		}
		
		$thisForumID = $forumDataOut['ID'];
		$thisForumTitle = $forumDataOut['ForumTitle'];
		$thisForumDescription = $forumDataOut['ForumDescription'];
		$thisCategoryID = $forumDataOut['CategoryID'];
		$thisCategoryName = $forumDataOut['CategoryName'];
	
		$topicList = getForumTopics( $requestedForumID, $offset, $count );
	
		$requestedForum = $thisForumTitle;
	}
	
	$errorCode = seekGET('e');
	$mobileBrowser = IsMobileBrowser();

	$pageTitle = "View forum: $thisForumTitle";
	
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
</head>

<body>

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
<div id='leftcontainer'>
	<div id="forums" class="left">
	
		<?php
		echo "<div class='navpath'>";
		echo "<a href='/forum.php'>Forum Index</a>";
		echo " &raquo; <a href='/forum.php?c=$thisCategoryID'>$thisCategoryName</a>";
		echo " &raquo; <b>$requestedForum</b></a>";
		echo "</div>";
		
		if( $numUnofficialLinks > 0 )
		{
			echo "</br><a href='/viewforum.php?f=0'><b>Administrator Notice:</b> $numUnofficialLinks unofficial posts need authorising: please verify them!</a></br>";
		}
	
		//echo "<h2 class='longheader'><a href='/forum.php?c=$nextCategoryID'>$nextCategory</a></h2>";
		echo "<h2>$requestedForum</h2>";
		echo "$thisForumDescription<br/>";

		if( $permissions >= \RA\Permissions::Registered )
			echo "<a href='createtopic.php?f=$thisForumID'><div class='rightlink'>[Create New Topic]</div></a>";
		
		echo "<table><tbody>";
		echo "<tr class='forumsheader'>";
		echo "<th colspan='2'>Topics</th>";
		echo "<th>Author</th>";
		echo "<th>Replies</th>";
		//echo "<th>Views</th>";
		echo "<th>Last post</th>";
		echo "</tr>";

		$topicCount = count( $topicList );
		
		$topicIter = 0;
		
		//	Output all topics, and offer 'prev/next page'
		foreach( $topicList as $topicData )
		{
			//var_dump( $topicData );
		
			//	Output one forum, then loop
			$nextTopicID = $topicData['ForumTopicID'];
			$nextTopicTitle = $topicData['TopicTitle'];
			$nextTopicPreview = $topicData['TopicPreview'];
			$nextTopicAuthor = $topicData['Author'];
			$nextTopicAuthorID = $topicData['AuthorID'];
			$nextTopicPostedDate = $topicData['ForumTopicPostedDate'];
			$nextTopicLastCommentID = $topicData['LatestCommentID'];
			$nextTopicLastCommentAuthor = $topicData['LatestCommentAuthor'];
			$nextTopicLastCommentAuthorID = $topicData['LatestCommentAuthorID'];
			$nextTopicLastCommentPostedDate = $topicData['LatestCommentPostedDate'];
			$nextTopicNumReplies = $topicData['NumTopicReplies'];
			
			if( $nextTopicPostedDate !== NULL )
				$nextTopicPostedNiceDate = getNiceDate( strtotime( $nextTopicPostedDate ) );
			else
				$nextTopicPostedNiceDate = "None";
				
			if( $nextTopicLastCommentPostedDate !== NULL )
				$nextTopicLastCommentPostedNiceDate = getNiceDate( strtotime( $nextTopicLastCommentPostedDate ) );
			else
				$nextTopicLastCommentPostedNiceDate = "None";
			
			echo "<tr>";
			
			echo "<td class='unreadicon'><img src='" . getenv('APP_STATIC_URL') . "/Images/ForumTopicUnread32.gif' width='20' height='20' title='No unread posts' alt='No unread posts'></img></td>";
			echo "<td class='topictitle'><a alt='Posted $nextTopicPostedNiceDate' title='Posted on $nextTopicPostedNiceDate' href='/viewtopic.php?t=$nextTopicID'>$nextTopicTitle</a><br/><div id='topicpreview'>$nextTopicPreview...</div></td>";
			echo "<td class='author'>";
			echo GetUserAndTooltipDiv( $nextTopicAuthor, NULL, NULL, NULL, NULL, $mobileBrowser );
			echo "</td>";
			//echo "<td class='author'><div class='author'><a href='/User/$nextTopicAuthor'>$nextTopicAuthor</a></div></td>";
			echo "<td class='replies'>$nextTopicNumReplies</td>";
			//echo "<td class='views'>$nextForumNumViews</td>";
			echo "<td class='lastpost'>";
			echo "<div class='lastpost'>";
			echo "<span class='smalldate'>$nextTopicLastCommentPostedNiceDate</span><br/>";
			echo GetUserAndTooltipDiv( $nextTopicLastCommentAuthor, NULL, NULL, NULL, NULL, $mobileBrowser );
			//echo "<a href='/User/$nextTopicLastCommentAuthor'>$nextTopicLastCommentAuthor</a>";
			echo " <a href='viewtopic.php?t=$nextTopicID&amp;c=$nextTopicLastCommentID' title='View latest post' alt='View latest post'>[View]</a>";
			echo "</div>";
			echo "</td>";
			echo "</tr>";
		}
		
		echo "</tbody></table>";
		
		echo "<br/>";
		
		if( $permissions >= \RA\Permissions::Registered )
		{
			echo "<a href='createtopic.php?f=$thisForumID'><div class='rightlink'>[Create New Topic]</div></a>";
		}
		else
		{
			echo "<div class='rightlink'><span class='hoverable' title='Unregistered: please check your email registration link!'>[Create New Topic]</span></div>";
		}
		
		echo "<br/>";
		
		?>
		
		<br/>
	</div> 
</div>
<div id='rightcontainer'>
	<?php
	if( $user !== NULL )
		RenderScoreLeaderboardComponent( $user, $points, TRUE );
	RenderRecentForumPostsComponent( 8 );
	?>
</div>
	 
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

