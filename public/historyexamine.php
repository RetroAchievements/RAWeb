<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

	$userPage = seekGET( 'u', $user );
	if( !isset( $userPage ) )
	{
		header( "Location: http://" . AT_HOST . "?e=notloggedin" );
		exit;
	}
	
	$dateInput = seekGET( 'd', 0 );
	
	$userPagePoints = getScore( $userPage );
	
	$achEarnedOnDay = getAchievementsEarnedOnDay( $dateInput, $userPage );
	
	$dateStr = strftime( "%d %b %Y", $dateInput );
	
	$errorCode = seekGET( 'e' );
	$pageTitle = "$userPage's Legacy - $dateStr";
	
	RenderDocType( TRUE );
	
	//error_reporting(E_ALL);

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
	echo "<div class='left'>";
	
		echo "<div class='navpath'>";
			echo "<a href='/userList.php'>All Users</a>";
			echo " &raquo; <a href='/User/$userPage'>$userPage</a>";
			echo " &raquo; <a href='history.php?u=$userPage'>History</a>";
			echo " &raquo; <b>$dateStr</b>";
		echo "</div>";
		
		echo "<h3 class='longheader'>$userPage's legacy - $dateStr</h3>";
		
		echo "<div class='userlegacy'>";
		echo "<img src='/UserPic/$userPage.png' alt='$userPage' align='right' width='64' height='64'>";
		echo "<b><a href='/User/$userPage'><strong>$userPage</strong></a> ($userPagePoints points)</b><br/><br/>";
		
		//echo "<a href='history.php?u=$userPage'>Back to $userPage's Legacy</a>";
		
		echo "<br/>";
		
		echo "</div>";
		
		echo "<table class='smalltable xsmall'><tbody>";
		
		//$sort1 = ($sortBy==1) ? 11 : 1;
		//$sort2 = ($sortBy==2) ? 12 : 2;
		//$sort3 = ($sortBy==3) ? 13 : 3;
				
		echo "<tr>";
		echo "<th>At</th>";
		echo "<th>Title</th>";
		echo "<th>Description</th>";
		echo "<th>Points</th>";
		echo "<th>Author</th>";
		echo "<th>Game Title</th>";
		echo "</tr>";
		
		//var_dump( $achEarnedOnDay );
		
		//	Merge if poss and count
		$achCount = count($achEarnedOnDay);
		$pointsCount = 0;
		//foreach( $achEarnedOnDay as $achEarned )
		
		//	Tally all
		for( $i = 0; $i < $achCount; $i++ )
		{
			$achID = $achEarnedOnDay[$i]['AchievementID'];
			$achPoints = $achEarnedOnDay[$i]['Points'];
			$pointsCount += $achPoints;
		}
		
		$achEarnedLib = Array();
		
		//	Store all NORMAL into $achEarnedLib
		for( $i = 0; $i < $achCount; $i++ )
		{
			$achID = $achEarnedOnDay[$i]['AchievementID'];
			//var_dump( $achEarnedOnDay[$i] );
			if( $achEarnedOnDay[$i]['HardcoreMode'] == 0 )
				$achEarnedLib[$achID] = $achEarnedOnDay[$i];
		}
		
		//	Potentially overwrite HARDCORE into $achEarnedLib
		for( $i = 0; $i < $achCount; $i++ )
		{
			$achID = $achEarnedOnDay[$i]['AchievementID'];
			if( $achEarnedOnDay[$i]['HardcoreMode'] == 1 )
			{
				//if( isset( $achEarnedLib[$achID] ) && $achEarnedLib[$achID]['HardcoreMode'] == 1 )
				//	Ordinary ach also exists: notify in points col
				$achEarnedLib[$achID] = $achEarnedOnDay[$i];
				$achPoints = $achEarnedLib[$achID]['Points'];
				$achEarnedLib[$achID]['PointsNote'] = "<span class='hardcore'>(+$achPoints)</span>"; 
			}
		}
		
		//
		function dateCompare($a, $b)
		{
			return $a['Date'] > $b['Date'];
		}
		usort( $achEarnedLib, "dateCompare" );
		
		foreach( $achEarnedLib as $achEarned )
		{
			$achAwardedAt	= $achEarned['Date'];
			$achID			= $achEarned['AchievementID'];
			$achTitle		= $achEarned['Title'];
			$achDesc		= $achEarned['Description'];
			$achPoints		= $achEarned['Points'];
			$achPointsNote	= isset( $achEarned['PointsNote'] ) ? $achEarned['PointsNote'] : '';
			$achAuthor 		= $achEarned['Author'];
			$achGameID		= $achEarned['GameID'];
			$achGameTitle	= $achEarned['GameTitle'];
			$achGameIcon	= $achEarned['GameIcon'];
			$achConsoleName	= $achEarned['ConsoleName'];
			$achBadgeName	= $achEarned['BadgeName'];
			$hardcoreMode	= $achEarned['HardcoreMode'];
			
			//$pointsCount 	+= $achPoints;
			
			//$dateUnix 		= strtotime( "$nextDay-$nextMonth-$nextYear" );
			//$dateStr 		= getNiceDate( $dateUnix, TRUE );
			
			echo "<tr>";
			
			echo "<td>";
			echo getNiceTime( strtotime( $achAwardedAt ) );
			echo "</td>";
			
			echo "<td style='min-width:25%'>";
			echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $achGameTitle, $achBadgeName, TRUE );
			echo "</td>";
			
			echo "<td style='min-width:25%'>";
			echo "$achDesc";
			if( $hardcoreMode )
				echo " <span class='hardcore'>(Hardcore!)</span>";
			echo "</td>";
			
			echo "<td>";
			echo "$achPoints $achPointsNote";
			echo "</td>";
			
			echo "<td>";
			echo GetUserAndTooltipDiv( $achAuthor, NULL, NULL, NULL, NULL, TRUE );
			echo "</td>";
			
			echo "<td>";
			echo GetGameAndTooltipDiv( $achGameID, $achGameTitle, $achGameIcon, $achConsoleName, TRUE, 32 );
			echo "</td>";
			
			
			echo "</tr>";
		}
		
		echo "</tbody></table>";
		
		echo "<h3>Summary</h3>";
		echo "<div class='historyexaminesummary'>";
		echo "Total earned on $dateStr: <strong>$pointsCount</strong> points, <strong>$achCount</strong> achievements.<br/><br/>";
		echo "<a href='/history.php?u=$userPage'>&laquo; Back to $userPage's Legacy</a><br/><br/>";
		echo "</div>";
		
	echo "</div>";
	
?>
</div>
<div id='rightcontainer'>
	<?php 
	if( $user !== NULL )
		RenderScoreLeaderboardComponent( $user, $points, $yOffset, TRUE );
	?>
</div>
	
</div>	

<?php RenderFooter(); ?>

</body>
</html>

