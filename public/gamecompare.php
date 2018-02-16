<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';

	$gameID = seekGET('ID');
	$user2 = seekGET('f');
	
	if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Unregistered ) )
	{
		header( "Location: http://" . AT_HOST . "?e=notloggedin" );
		exit;
	}
	
	$totalFriends = getAllFriendsProgress( $user, $gameID, $friendScores );
	
	$numAchievements = getGameMetadata( $gameID, $user, $achievementData, $gameData, 0, $user2 );
	
	$consoleID = $gameData['ConsoleID'];
	$consoleName = $gameData['ConsoleName'];
	$gameTitle = $gameData['Title'];
	
	$gameIcon = $gameData['ImageIcon'];
	
	$errorCode = seekGET( 'e' );
	$pageTitle = "Game Compare";
	
	$gamesPlayedWithAchievements = array();
	$numGamesPlayedWithAchievements = 0;
	
	$numGamesPlayed = getUsersGameList( $user, $userGamesList );
	
	foreach( $userGamesList as $nextGameID => $nextGameData )
	{
		$nextGameTitle 			 = $nextGameData['Title'];
		$nextConsoleName 		 = $nextGameData['ConsoleName'];
		
		$numAchieved 			 = $nextGameData['NumAchieved'];
		$numPossibleAchievements = $nextGameData['NumAchievements'];
		$gamesPlayedWithAchievements[$nextGameID] = "$nextGameTitle ($nextConsoleName) ($numAchieved / $numPossibleAchievements won)";
	}
	
	asort( $gamesPlayedWithAchievements );

	//	Quickly calculate earned/potential
	$totalEarned = 0;
	$totalPossible = 0;
	$numEarned = 0;
	if( isset( $achievementData ) )
	{
		foreach( $achievementData as &$achievement )
		{
			$totalPossible += $achievement['Points'];
			if( isset( $achievement['DateAwarded'] ) )
			{
				$numEarned++;
				$totalEarned += $achievement['Points'];
			}
		}
	}
	
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

	<div id="gamecompare" class="left">
	  <?php 
		echo "<div class='navpath'>";
		echo "<a href='/gameList.php'>All Games</a>";
		echo " &raquo; <a href='/gameList.php?c=EXTERNAL_FRAGMENT'>$consoleID$consoleName</a>";
		echo " &raquo; <a href='/Game/$gameID'>$gameTitle</a>";
		echo " &raquo; <b>Game Compare</b>";
		echo "</div>";
		
		echo "<h3 class='longheader'>Game Compare</h3>";
	  
		$pctAwarded = 0;
		if( $numAchievements > 0 )
			$pctAwarded = sprintf( "%01.0f", ( $numEarned * 100.0 / $numAchievements ) );
		
		echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, FALSE, 96 );
		//echo "<a href='/Game/$gameID'>$gameTitle ($consoleName)</a><br/><br/>";
		
		echo "<form method=get action='/public/gamecompare.phpare.php'>";
		echo "<input type='hidden' name='f' value='$user2'>";
		echo "<select name='ID'>";
		foreach( $gamesPlayedWithAchievements as $nextGameID => $nextGameTitle )
		{
			$selected = ( $nextGameID == $gameID ) ? "SELECTED" : "";
			echo "<option value='$nextGameID' $selected>$nextGameTitle</option>";
		}
		echo "</select>";
		echo "&nbsp;<input value='Change Game' type='submit' size='67'>";
		echo "</form>";
		echo "<br/>";
		
		echo "There are <b>$numAchievements</b> achievements worth <b>$totalPossible</b> points.<br/>";
		
		$iconSize = 48;
		
		echo "<table class='smalltable gamecompare'><tbody>";
		echo "<tr>";
		
		echo "<th>";
		echo "<a style='float: right' href='/User/$user'>$user</a><br/>";
		echo "<img style='float: right' alt='$user' title='$user' src='/UserPic/$user.png' width='$iconSize' height='$iconSize' />";
		echo "</th>";
		
		echo "<th><center>Achievement</center></th>";
		
		echo "<th>";
		echo "<a style='float: left' href='/User/$user2'>$user2</a><br/>";
		echo "<img style='float: left' alt='$user2' title='$user2' src='/UserPic/$user2.png' width='$iconSize' height='$iconSize' />";
		echo "</th>";
		
		echo "</tr>";
		
		$leftAwardedCount = 0;
		$rightAwardedCount = 0;
		$leftAwardedPoints = 0;
		$rightAwardedPoints = 0;
		$maxPoints = 0;
		
		$achIter = 0;
		foreach( $achievementData as $nextAch )
		{
			if( $achIter++%2==0 )
				echo "<tr>";
			else
				echo "<tr class='alt'>";
			
			$achID = $nextAch['ID'];
			$achTitle = $nextAch['Title'];
			$achDesc = $nextAch['Description'];
			$achPoints = $nextAch['Points'];
			
			$maxPoints += $achPoints;
			
			$badgeName = $nextAch['BadgeName'];
			
			//var_dump( $nextAch );
			$awardedLeft = isset( $nextAch['DateEarned'] ) ? $nextAch['DateEarned'] : NULL;
			$awardedRight = isset( $nextAch['DateEarnedFriend'] ) ? $nextAch['DateEarnedFriend'] : NULL;
			$awardedHCLeft = isset( $nextAch['DateEarnedHardcore'] ) ? $nextAch['DateEarnedHardcore'] : NULL;
			$awardedHCRight = isset( $nextAch['DateEarnedHardcoreFriend'] ) ? $nextAch['DateEarnedHardcoreFriend'] : NULL;
			
			echo "<td class='awardlocal'>";
			if( isset( $awardedLeft ) )
			{
				if( isset( $awardedHCLeft ) )
				{
					echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, TRUE, TRUE, "", $iconSize, "goldimage awardLocal" );
					$leftAwardedCount++;
					$leftAwardedCount++;
					$leftAwardedPoints += $achPoints;
					$leftAwardedPoints += $achPoints;
					
					echo "<small class='smalldate rightfloat'>-=HARDCORE=-<br/>unlocked on<br/>$awardedHCLeft</small>";
				}
				else
				{
					echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, TRUE, TRUE, "", $iconSize, "awardLocal" );
					$leftAwardedCount++;
					$leftAwardedPoints += $achPoints;
					
					echo "<small class='smalldate rightfloat'>unlocked on<br/>$awardedLeft</small>";
				}
			}
			else
			{
				echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName . "_lock", TRUE, TRUE, "", $iconSize, "awardLocal" );
				//echo "<img class='awardlocal' src='http://i.retroachievements.org/Badge/$badgeName" . "_lock.png' alt='$achTitle' align='right' width='$iconSize' height='$iconSize'>";
			}
			echo "</td>";
			
			echo "<td class='comparecenter'>";
			echo "<p>";
			echo "<a href=\"Achievement/$achID\"><strong>$achTitle</strong></a><br/>";
			echo "$achDesc<br/>";
			echo "($achPoints Points)";
			echo "</p>";
			echo "</td>";
			
			echo "<td class='awardremote'>";
			if( isset( $awardedRight ) )
			{
				if( isset( $awardedHCRight ) )
				{
					echo "<div style='float:right;' >";
					echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, TRUE, TRUE, "", $iconSize, "goldimage awardremote" );
					echo "</div>";
					$rightAwardedCount++;
					$rightAwardedCount++;
					$leftAwardedPoints += $achPoints;
					$rightAwardedPoints += $achPoints;
					
					echo "<small class='smalldate leftfloat'>-=HARDCORE=-<br/>unlocked on<br/>$awardedHCRight</small>";
				}
				else
				{
					echo "<div style='float:right;' >";
					echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, TRUE, TRUE, "", $iconSize, "awardremote" );
					echo "</div>";
					$rightAwardedCount++;
					$rightAwardedPoints += $achPoints;
					
					echo "<small class='smalldate leftfloat'>unlocked on<br/>$awardedRight</small>";
				}
			}
			else
			{
				echo "<div style='float:right;' >";
				echo "<img class='awardremote' src='http://i.retroachievements.org/Badge/$badgeName" . "_lock.png' alt='$achTitle' align='left' width='$iconSize' height='$iconSize'>";
				echo "</div>";
			}
			echo "</td>";
			
			echo "</tr>";
		}
		
		//	Repeat user images:
		echo "<tr>";
		
		echo "<td>";
		echo "<div style='float:right'>";
		echo GetUserAndTooltipDiv( $user, NULL, NULL, NULL, NULL, TRUE, NULL, 48, "badgeimg rightfloat" );
		echo "</div>";
		echo "</td>";
		
		echo "<td></td>";
		
		echo "<td>";
		echo "<div>";
		echo GetUserAndTooltipDiv( $user2, NULL, NULL, NULL, NULL, TRUE, NULL, 48 );
		echo "</div>";
		echo "</td>";
		
		echo "</tr>";
		
		//	Draw totals:
		echo "<tr>";
		echo "<td class='rightfloat'>";
		echo "<b>$leftAwardedCount</b>/$numAchievements awarded<br/><b>$leftAwardedPoints</b>/$maxPoints points";
		echo "</td>";
		echo "<td></td>";
		echo "<td class='leftfloat'>";
		echo "<b>$rightAwardedCount</b>/$numAchievements awarded<br/><b>$rightAwardedPoints</b>/$maxPoints points";
		echo "</td>";
		echo "</tr>";
		
		echo "</tbody></table>";
		
		echo "<br/><br/>";
		
		//echo "<small><a href=\"http://retroachievements.org/wiki/index.php?title=$gameTitle\">Developer Wiki Link</a></small>";
		?>
	 </div>
</div>
<div id='rightcontainer'>

<?php
	echo "<div id='gamecompare' class='right'>";
	$offset = 0;
	echo "<h3>Friends</h3>";
	if( isset( $friendScores ) )
	{
		echo "<div class='nicebox'>";
		echo "Compare to your friend:<br/>";
		$i = 0;
		echo "<table class='smalltable'><tbody>";
		foreach( $friendScores as $friendScoreName => $friendData )
		{
			$link = "/gamecompare.php?ID=$gameID&f=$friendScoreName";
			if( $i++ % 2 == 1)
				echo "<tr>";
			else
				echo "<tr class='alt'>";
			
			echo "<td>";
			echo GetUserAndTooltipDiv( $friendScoreName, $friendData['RAPoints'], $friendData['Motto'], "", $friendData['LastUpdate'], TRUE, $link );
			echo GetUserAndTooltipDiv( $friendScoreName, $friendData['RAPoints'], $friendData['Motto'], "", $friendData['LastUpdate'], FALSE, $link );
			echo "</td>";
			
			echo "<td>";
			echo "<a href='$link'>";
			echo $friendData['TotalPoints']. "/$totalPossible";
			echo "</a>";
			echo "</td>";
			
			echo "</tr>";
			$offset += 44;
		}
		
		echo "</tbody></table>";
	
		echo "</br>";
		echo "Compare with any user:<br/>";
		
		echo "<form method='get' action='/public/gamecompare.phpare.php'>";
		echo "<input type='hidden' name='ID' value='$gameID'>";
		echo "<input size='28' name='f' type='text' class='searchboxgamecompareuser' />";
		echo "&nbsp;<input type='submit' value='Select' />";
		echo "</form>";
		
		echo "</div>";
	}
	else
	{
		echo "<div class='nicebox'>";
		if( $totalFriends > 0 )
		{
			echo "None of your friends appear to have won any achievements for $gameTitle!<br/>";
			echo "Brag about your achievements to them <a href='friends.php'>on their wall</a>!<br/>";
		}
		else
		{
			echo "RetroAchievements is a lot more fun with friends!<br/><br/>";
			echo "Find friends to add <a href='/userList.php'>here</a>!<br/>";
		}
		echo "<br/>";
		
		echo "or compare against any user:<br/>";
		
		echo "<form method='get' action='/public/gamecompare.phpare.php'>";
		echo "<input type='hidden' name='ID' value='$gameID'>";
		echo "<input size='28' name='f' type='text' class='searchboxgamecompareuser' />";
		echo "&nbsp;<input type='submit' value='Select' />";
		echo "</form>";
		
		echo "</div>";
	}
	
	echo "<br/><br/>";
	
	echo "</div>";
	
	?>
	
</div>

	 
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

