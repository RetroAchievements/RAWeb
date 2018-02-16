<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

	$maxCount = 50;
	
	$count = seekGET( 'c', $maxCount );
	$offset = seekGET( 'o', 0 );
	$method = seekGET( 'p', 0 );
	settype( $method, 'integer' );

	$gameData = getMostPopularGames( $offset, $count, $method );
	
	$pageTitle = "Most Popular Games";

	$mobileBrowser = IsMobileBrowser();
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

		<?php
		echo "<div class='navpath'>";
		echo "<b>$pageTitle</b>";	//	NB. This will be a stub page
		echo "</div>";
		
		echo "<div class='detaillist'>";
		
		echo "<h3 class='longheader'>$pageTitle</h3>";
		
		echo "Show: | ";
		
		if( $method==0 ) 	echo "by number of awards given";
		else 				echo "<a href='/popularGames.phpmes.php?p=0'>by number of awards given</a>";
			
		echo " | ";
			
		if( $method==1 )	echo "by unique members played ";
		else				echo "<a href='/popularGames.phpmes.php?p=1'>by unique members played</a> ";
		
		echo " | ";
			
		echo "<table class='smalltable xsmall'><tbody>";
		
		echo "<th>Rank</th>";
		echo "<th>Game</th>";
		if(!$mobileBrowser)
		{
			echo "<th>Publisher</th>";
			echo "<th>Developer</th>";
		}
		echo "<th>Genre</th>";
		
		$countCol = ( $method == 0 ) ? "Awards Given" : "Played By";
		echo "<th>$countCol</th>";
		
		$count = 0;
		
		foreach( $gameData as $gameEntry )
		{
			$count++;

			$gameID = $gameEntry['ID'];
			$gameTitle = $gameEntry['Title'];
			$gameForumTopicID = $gameEntry['ForumTopicID'];
			$gameFlags = $gameEntry['Flags'];
			$gameIcon = $gameEntry['ImageIcon'];
			$gamePublisher = $gameEntry['Publisher'];
			$gameDeveloper = $gameEntry['Developer'];
			$gameGenre = $gameEntry['Genre'];
			$gameReleased = $gameEntry['Released'];
			$consoleName = $gameEntry['ConsoleName'];
			$numRecords = $gameEntry['NumRecords'];
			
			echo "<tr>";
			
			echo "<td>";
			echo ($count)+($offset);
			echo "</td>";
			
			echo "<td style='min-width:30%'>";
			echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName );
			echo "</td>";
			
			if(!$mobileBrowser)
			{
			echo "<td>";
			echo "$gamePublisher";
			echo "</td>";
			
			echo "<td>";
			echo "$gameDeveloper";
			echo "</td>";
			}
			
			echo "<td>";
			echo "$gameGenre";
			echo "</td>";
			
			echo "<td>";
			echo "$numRecords";
			echo "</td>";
			
			echo "</tr>";
		}
		
		echo "</tbody></table>";
		echo "</div>";
		
		echo "<div class='rightalign row'>";
		if( $offset > 0 )
		{
			$prevOffset = $offset - $maxCount;
			echo "<a href='/popularGames.phpmes.php?o=$prevOffset&amp;p=EXTERNAL_FRAGMENT'>&lt; Previous $method$maxCount</a> - ";
		}
		if( $count == $maxCount )
		{
			//	Max number fetched, i.e. there are more. Can goto next 25.
			$nextOffset = $offset + $maxCount;
			echo "<a href='/popularGames.php?o=$nextOffset&amp;p=EXTERNAL_FRAGMENT'>Next $method$maxCount &gt;</a>";
		}
		echo "</div>";
		
		?>
		<br/>
</div>
<div id='rightcontainer'>
	<?php RenderRecentlyUploadedComponent( 10 ); ?>
</div>
	
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

