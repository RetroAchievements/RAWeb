<?php
require_once __DIR__ . '/../lib/bootstrap.php';
header('Location: '.getenv('APP_URL'));
exit;

	$consoleList = getConsoleList();
	$consoleIDInput = seekGET( 'i', 0 );
	
	settype( $consoleIDInput, 'integer' );

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

	$maxCount = 25;
	
	$count = seekGet( 'c', $maxCount );
	$offset = seekGet( 'o', 0 );

	$params = seekGet( 'p', 0 );
	settype( $params, 'integer' );
	
	if( $user == NULL )
		$params = 0;
	
	$sortBy = seekGet( 's', 1 );
	
	getCommonlyEarnedAchievements( $consoleIDInput, $offset, $count, $awardedData );
	
	//var_dump( $awardedData );
	
	$requestedConsole = "";
	if( $consoleIDInput !== 0 )
		$requestedConsole = " " . $consoleList[$consoleIDInput];
	
	$pageTitle = "Achievement List" . $requestedConsole;

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
	<div class='left'>
		<?php
		echo "<div class='navpath'>";
		echo "<b>Most Awarded Achievements</b>";	//	NB. This will be a stub page
		echo "</div>";
		
		echo "<div class='detaillist'>";
		
		$subtitle = "";
		if( $requestedConsole !== "" )
			$subtitle = " - $requestedConsole";
		
		echo "<h3 class='longheader'>Most Awarded Achievements List$subtitle</h3>";
		
		// echo "Showing:</br>";
		
		// echo "&nbsp;- ";
		// if( $params !== 0 ) echo "<a href='/achievementList.php?s=$sortBy&p=0'>"; 
		// else echo "<b>";
		// echo "All achievements";
		// if( $params !== 0 ) echo "</a>";
		// else echo "</b>";
		// echo "<br/>";
		
		echo "Show: ";
		
		echo "<a href='awardedList.php?s=$sortBy&amp;o=0&amp;p=$params&amp;i=0'>All consoles</a>";
		
		foreach( $consoleList as $nextConsoleID => $nextConsoleName )
		{
			if( $nextConsoleID == $consoleIDInput )
				echo ", <b>$nextConsoleName</b>";
			else
				echo ", <a href='awardedList.php?s=$sortBy&amp;o=0&amp;p=$params&amp;i=$nextConsoleID'>$nextConsoleName</a>";
		}
		
		echo "<br/>";
		
		// if( $user !== NULL )
		// {
			// echo "&nbsp;- ";
			// if( $params !== 1 ) echo "<a href='/achievementList.php?s=$sortBy&p=1'>"; 
			// else echo "<b>";
			// echo "Only my earned achievements";
			// if( $params !== 1 ) echo "</a>";
			// else echo "</b>";
			// echo "<br/>";
			
			// echo "&nbsp;- ";
			// if( $params !== 2 ) echo "<a href='/achievementList.php?s=$sortBy&p=2'>"; 
			// else echo "<b>";
			// echo "Achievements I haven't yet won";
			// if( $params !== 2 ) echo "</a>";
			// else echo "</b>";
			// echo "<br/>";
		// }
		
		//echo "<div class='rightfloat'>* = ordered by</div>";
		
		echo "<table class='smalltable xsmall'><tbody>";
		
		//$sort1 = ($sortBy==1) ? 11 : 1;
		//$sort2 = ($sortBy==2) ? 12 : 2;
		//$sort3 = ($sortBy==3) ? 13 : 3;
		//$sort4 = ($sortBy==4) ? 14 : 4;
		//$sort5 = ($sortBy==5) ? 15 : 5;
		
		//$mark1 = ($sortBy%10==1) ? '&nbsp;*' : '';
		//$mark2 = ($sortBy%10==2) ? '&nbsp;*' : '';
		//$mark3 = ($sortBy%10==3) ? '&nbsp;*' : '';
		//$mark4 = ($sortBy%10==4) ? '&nbsp;*' : '';
		//$mark5 = ($sortBy%10==5) ? '&nbsp;*' : '';
		
		//echo "<th><a href='/achievementList.php?s=$sort1&p=$params'>Title</a>$mark1</th>";
		//echo "<th><a href='/achievementList.php?s=$sort2&p=$params'>Description</a>$mark2</th>";
		//echo "<th><a href='/achievementList.php?s=$sort3&p=$params'>Points</a>$mark3</th>";
		//echo "<th><a href='/achievementList.php?s=$sort4&p=$params'>Author</a>$mark4</th>";
		//echo "<th><a href='/achievementList.php?s=$sort5&p=$params'>Game Title</a>$mark5</th>";
		
		echo "<th>Title</th>";
		echo "<th>Description</th>";
		echo "<th>Points</th>";
		echo "<th>Game Title</th>";
		echo "<th>Times Awarded</th>";
		
		$achCount = 0;
		
		foreach( $awardedData as $achEntry )
		{
			$achID = $achEntry['ID'];
			$achTitle = $achEntry['AchievementTitle'];
			$achDesc = $achEntry['Description'];
			$achPoints = $achEntry['Points'];
			$achAuthor = $achEntry['Author'];
			$achDateCreated = $achEntry['DateCreated'];
			$achDateModified = $achEntry['DateModified'];
			$achBadgeName = $achEntry['BadgeName'];
			$gameID = $achEntry['GameID'];
			$gameIcon = $achEntry['GameIcon'];
			$gameTitle = $achEntry['GameTitle'];
			$consoleName = $achEntry['ConsoleName'];
			$timesAwarded = $achEntry['NumTimesAwarded'];
			
			if( $achCount++%2 == 0 )
				echo "<tr>";
			else
				echo "<tr class='alt'>";
			
			echo "<td style='min-width:25%'>";
			echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, TRUE );
			echo "</td>";
			
			echo "<td style='min-width:25%'>";
			echo "$achDesc";
			echo "</td>";
			
			echo "<td>";
			echo "$achPoints";
			echo "</td>";
			
			//echo "<td>";
			//echo GetUserAndTooltipDiv( $achAuthor, NULL, NULL, NULL, NULL, FALSE );
			//echo "</td>";
			
			echo "<td>";
			echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName );
			echo "</td>";
			
			echo "<td>";
			echo "$timesAwarded";
			echo "</td>";
			
			echo "</tr>";
		}
		
		echo "</tbody></table>";
		echo "</div>";
		
		echo "<div class='rightalign row'>";
		if( $offset > 0 )
		{
			$prevOffset = $offset - $maxCount;
			echo "<a href='/awardedList.php?s=$sortBy&amp;o=$prevOffset&amp;p=$params&amp;i=$consoleIDInput'>&lt; Previous $maxCount</a> - ";
		}
		if( $achCount == $maxCount )
		{
			//	Max number fetched, i.e. there are more. Can goto next 25.
			$nextOffset = $offset + $maxCount;
			echo "<a href='/awardedList.php?s=$sortBy&amp;o=$nextOffset&amp;p=$params&amp;i=$consoleIDInput'>Next $maxCount &gt;</a>";
		}
		echo "</div>";
		
		?>
		<br/>
	</div> 
</div>

<div id='rightcontainer'>
	<?php RenderRecentlyUploadedComponent( 10 ); ?>
</div>

</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

