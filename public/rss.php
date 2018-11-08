<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	$pageTitle = "RSS Streams";

	$errorCode = seekGET('e');
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
	<?php
	$yOffs = 0;
	//RenderTwitchTVStream( $yOffs );
	//RenderChat( $user, $yOffs );
	?>
	
	<div id="rsslist" class="left">
		
		<?php
		echo "<div class='navpath'>";
		echo "<b>RSS List</b>";
		echo "</div>";
		
		echo "<div class='largelist'>";
		
		echo "<h2 class='longheader'>What is RSS?</h2>";
		echo "<p>RSS allows you to easily receive a stream of the latest content and activity on a website or community, and we have a few streams available on RetroAchievenents.org. ";
		echo "If you'd like to know more about RSS, visit <a href='http://www.whatisrss.com/'>What Is RSS.com</a></p>";
		
		echo "<h2 class='longheader'>RSS Streams</h2>";
		echo "<p>Please help yourself to the following streams. More will be added soon!<br/><br/>";
		echo "<a href='/rss-news'><img src='" . getenv('APP_STATIC_URL') . "/Images/rss_icon.gif' width='41' height='13' />&nbsp;News Stream</a><br/>";
		echo "<a href='/rss-newachievements'><img src='" . getenv('APP_STATIC_URL') . "/Images/rss_icon.gif' width='41' height='13' />&nbsp;Newly Created Achievements</a><br/>";
		echo "<a href='/rss-activity'><img src='" . getenv('APP_STATIC_URL') . "/Images/rss_icon.gif' width='41' height='13' />&nbsp;Global Activity (Very busy!)</a><br/>";
		echo "<a href='/rss-forum'><img src='" . getenv('APP_STATIC_URL') . "/Images/rss_icon.gif' width='41' height='13' />&nbsp;Forum Activity</a><br/>";
		if( isset( $user ) )
		{
			echo "<a href='/rss-activity?u=$user'><img src='" . getenv('APP_STATIC_URL') . "/Images/rss_icon.gif' width='41' height='13' />&nbsp;$user's Friends Stream</a><br/>";
		}
		echo "<br/></p>";
		
		echo "</div>";
		?>
		
		<br/>
	</div> 
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

