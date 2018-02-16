<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';

	$requestedCategoryID = seekGET( 'c' );
	settype( $requestedCategoryID, "integer" );
	
	$forumList = getForumList( $requestedCategoryID );

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

	$numUnofficialLinks = 0;
	if( $permissions >= \RA\Permissions::Developer )
	{
		$unofficialLinks = getUnauthorisedForumLinks();
		$numUnofficialLinks = count( $unofficialLinks );
	}

	$pageTitle = "Forum Index";
	$requestedCategory = "";
	if( $requestedCategoryID !== 0 && count( $forumList ) > 0 )
	{
		$requestedCategory = $forumList[0]['CategoryName'];	//	Fetch any elements data
		$pageTitle .= ": " . $requestedCategory;
	}
	
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
<div id='leftcontainer'>

	<?php RenderErrorCodeWarning( 'left', $errorCode ); ?>
	<div id="forums" class="left">
		
		<?php
		echo "<div class='navpath'>";
		if( $requestedCategory == "" )
		{
			echo "<b>Forum Index</b>";
		}
		else
		{
			echo "<a href='/forum.phprum.php'>Forum Index</a>";
			echo " &raquo; <b>$requestedCategory</b></a>";	
		}
		echo "</div>";
		
		//	Output all forums fetched, by category

		if( $numUnofficialLinks > 0 )
		{
			echo "</br><a href='/viewforum.php?f=0'><b>Developer Notice:</b> $numUnofficialLinks unofficial posts need authorising: please verify them!</a></br>";
		}
	
		$lastCategory = "_init";
	
		$forumIter = 0;
		
		foreach( (array)$forumList as $forumData )
		{
			$nextCategory = $forumData['CategoryName'];	
			$nextCategoryID = $forumData['CategoryID'];	
			
			if( $nextCategory != $lastCategory )
			{
				if( $lastCategory !== "_init" )
				{
					//	We are starting another table, but we need to close the last one!
					echo "</tbody>";
					echo "</table>";
					echo "<br/>";
					echo "<br/>";
					$forumIter = 0;
				}
				
				echo "<h2>Forum: $nextCategory</h2>";
				echo $forumData['CategoryDescription'] . "<br/>";
				
				echo "<table>";
				echo "<tbody>";
				echo "<tr>";
				echo "<th colspan='2'>Forum</th>";
				echo "<th>Topics</th>";
				echo "<th>Posts</th>";
				echo "<th>Last Post</th>";
				echo "</tr>";
				
				$lastCategory = $nextCategory;
			}
			
			//	Output one forum, then loop
			$nextForumID = $forumData['ID'];
			$nextForumTitle = $forumData['Title'];
			$nextForumDesc = $forumData['Description'];
			$nextForumNumTopics = $forumData['NumTopics'];
			$nextForumNumPosts = $forumData['NumPosts'];
			$nextForumLastPostCreated = $forumData['LastPostCreated'];
			if( $nextForumLastPostCreated !== NULL )
				$nextForumCreatedNiceDate = date( "d M, Y H:i", strtotime( $nextForumLastPostCreated ) );
			else
				$nextForumCreatedNiceDate = "None";
			$nextForumLastPostAuthor = $forumData['LastPostAuthor'];
			$nextForumLastPostTopicName = $forumData['LastPostTopicName'];
			$nextForumLastPostTopicID = $forumData['LastPostTopicID'];
			$nextForumLastPostID = $forumData['LastPostID'];
			
			echo "<tr>";
				
			echo "<td class='unreadicon'><img title='$nextForumTitle' alt='$nextForumTitle' src='http://i.retroachievements.org/Images/ForumTopicUnread32.gif' width='32' height='32'></img></td>";
			echo "<td class='forumtitle'><a href='/viewforum.php?f=EXTERNAL_FRAGMENT'>$nextForumID$nextForumTitle</a><br/>";
			echo "$nextForumDesc</td>";
			echo "<td class='topiccount'>$nextForumNumTopics</td>";
			echo "<td class='postcount'>$nextForumNumPosts</td>";
			echo "<td class='lastpost'>";
			echo "<div class='lastpost'>";
			echo "<span class='smalldate'>$nextForumCreatedNiceDate</span><br/>";
			if( isset( $nextForumLastPostAuthor ) && strlen( $nextForumLastPostAuthor ) > 1 )
			{
				echo GetUserAndTooltipDiv( $nextForumLastPostAuthor, NULL, NULL, NULL, NULL, TRUE );
				//echo "<a href='/User/$nextForumLastPostAuthor'>$nextForumLastPostAuthor</a>";
				echo " <a href='/viewtopic.php?t=$nextForumLastPostTopicID&c=EXTERNAL_FRAGMENT'>[View]</a>$nextForumLastPostID";
			}
			echo "</div>";
			echo "</td>";
			echo "</tr>";
		}

		echo "</tbody></table>";
		
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

