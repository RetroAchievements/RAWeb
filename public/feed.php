<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$errorCode = seekGET( 'e' );
	$offset = seekGET( 'o' );
	$global = seekGET( 'g', NULL );
	$activityID = seekGET( 'a', NULL );
	$individual = seekGET( 'i', NULL );
	
	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
 
	//	Max: last 50 messages:
	$maxMessages = 50;
	
	if( $activityID !== NULL )
	{
		$numFeedItems = getFeed( $user, $maxMessages, $offset, $feedData, $activityID, 'activity' );
	}
	else if( isset( $global ) )
	{
		$numFeedItems = getFeed( $user, $maxMessages, $offset, $feedData, 0, 'global' );
		$global = TRUE;
	}
	else if( isset( $user ) && !isset( $individual ) )
	{
		$numFeedItems = getFeed( $user, $maxMessages, $offset, $feedData, 0, 'friends' );
	}
	else if( isset( $individual ) )
	{
		$numFeedItems = getFeed( $user, $maxMessages, $offset, $feedData, 0, 'individual' );
	}

	//var_dump( $feedData );
	
	//	This page is unusual, in that the later items should appear at the top
	$feedData = array_reverse( $feedData );
	
	if( isset( $activityID ) )
		$pageTitle = "Activity";
	else if( $global )
		$pageTitle = "Global Activity Feed";
	else if( isset( $user ) )
		$pageTitle = $user . "'s Activity Feed";
	else
		$pageTitle = "Activity Feed";
	
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<script type='text/javascript'>
		$(document).ready(function() {
			FocusOnArticleID( GetParameterByName( "a" ) );
		});
	</script>
	<?php RenderGoogleTracking(); ?>
	<link rel='alternate' type='application/rss+xml' title='Global Feed' href='http://retroachievements.org/rss-activity' />
</head>

<body onload="init_chat(50);">	
<script type='text/javascript' src="/public/js/all.js"></script>
<script type='text/javascript' src="/public/js/ping_chat.js"></script>
	
<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
	<div id='leftcontainer'>
	
	<div id="globalfeed" class="left">
		<h2><?php echo $pageTitle; ?></h2>
		<?php
			echo "<table width='550' id='feed' style='width:100%' ><tbody>";
			
			$lastID = 0;
			$lastKnownDate = 'Init';
			
			for( $i = 0; $i < $numFeedItems; $i++ )
			{
				$nextTime = $feedData[$i]['timestamp'];
				
				$dow = date( "d/m", $nextTime );
				if( $lastKnownDate == 'Init' )
				{
					$lastKnownDate = $dow;
					echo "<tr><td class='date'>$dow:</td></tr>";
				}
				else if( $lastKnownDate !== $dow )
				{
					$lastKnownDate = $dow;
					echo "<tr><td class='date'><br/>$dow:</td></tr>";
				}
				
				if( $lastID != $feedData[$i]['ID'] )
				{
					$lastID = $feedData[$i]['ID'];
					RenderFeedItem( $feedData[$i], $user );
				}
				
				if( $feedData[$i]['Comment'] !== NULL )
				{
					while( ($i < $numFeedItems) && $lastID == $feedData[$i]['ID'] )
					{
						RenderArticleComment( $feedData[$i]['ID'], $feedData[$i]['CommentUser'], $feedData[$i]['CommentPoints'], $feedData[$i]['CommentMotto'], $feedData[$i]['Comment'], $feedData[$i]['CommentPostedAt'], $user, 0, $feedData[$i]['CommentID'], false );
						$i++;
					}
					$i--;	//Note: we will have incorrectly incremented this if we read comments - the first comment has the same ID!
				}
				
			}
			echo "</tbody></table>";
			
			echo "<div class='rightalign row'>";
			
			if( $offset > 0 )
			{
				echo "<a href='/feed.php?";
				if( $global )
					echo "g=1&amp;";
				echo "o=" . ($offset - 50);
				echo "'>&lt; Previous 50</a> - ";
			}
			
			if( $activityID !== NULL )
			{
				echo "<a href='/feed.phpeed.php?g=1'>Global Feed &gt;</a> ";
			}
			else if( $numFeedItems > 0 )
			{
				echo "<a href='/feed.php?";
				if( $global )
					echo "g=1&amp;";
				echo "o=" . ($offset + 50);
				echo "'>Next 50 &gt;</a> ";
			}
			
			echo "</div>";
			
		?>
	</div> 
	
	</div>
	
	<div id='rightcontainer' >
	<?php 
		$yOffs = 0;
		RenderTwitchTVStream();
		RenderChat( $user );
	?>
	
	<div id="achievement" class="rightFeed">
	</div>
	
	</div>
	
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

