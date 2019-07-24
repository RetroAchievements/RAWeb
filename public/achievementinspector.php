<?php
	require_once __DIR__ . '/../lib/bootstrap.php';

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	$modifyOK = ( $permissions >= \RA\Permissions::Developer );
	
	$gameID = seekGET( 'g' );
	$errorCode = seekGET( 'e' );
	
	$achievementList = array();
	$gamesList = array();
	
	$codeNotes = array();
		
	$gameIDSpecified = ( isset( $gameID ) && $gameID != 0 );
	if( $gameIDSpecified )
	{
		getGameMetadata( $gameID, $user, $achievementData, $gameData );
		$gameTitle = $gameData['Title'];
		$consoleName = $gameData['ConsoleName'];
		$gameIcon = $gameData['ImageIcon'];
		
		getCodeNotes( $gameID, $codeNotes );
	}
	else
	{
		getGamesList( 0, $gamesList );
	}
	
	$pageTitle = "Manage Achievements";
	
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
	echo "<div id='leftcontainer'>";
	echo "<div id='warning' class='rightfloat'>Status: OK!</div>";
	
	echo "<h2 class='longheader'>Achievement Inspector</h2>";
	
	if( $gameIDSpecified ) 
	{
		echo "Reordering achievements for:<br/>";
		echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, FALSE, 96 );
		echo "<br/>";
		echo "<span class='clickablebutton'><a href='/achievementinspector.php?g=$gameID'>Refresh Page</a></span><br/>";
		echo "<span class='clickablebutton'><a href='/achievementinspector.php'>Back to List</a></span><br/>";
		
		if( $modifyOK )
		{
			echo "<p><b>Instructions:</b> This is the game's achievement list as displayed on the website or in the emulator. " .
				 "The achievements will be ordered by 'Display Order', the column found on the right, in order from smallest to greatest. " .
				 "Adjust the numbers on the right to set an order for them to appear in. Any changes you make on this page will instantly " .
				 "take effect on the website, but you will need to press 'Refresh List' to see the new order on this page.</p><br/>";
		}
	
		echo "<table><tbody>";
		echo "<tr>";
		echo "<th>ID</th>";
		echo "<th>Badge</th>";
		echo "<th>Title</th>";
		echo "<th>Description</th>";
		//echo "<th>Mem</th>";
		echo "<th>Points</th>";
		echo "<th>Created/Modified</th>";
		echo "<th>Display Order</th>";
		//echo "<th>Submit</th>";
		echo "</tr>";
		
		//	Display all achievements
		foreach( (array)$achievementData as $achievementEntry )
		{
			$achID = $achievementEntry['ID'];
			//$gameConsoleID = $achievementEntry['ConsoleID'];
			$achTitle = $achievementEntry['Title'];
			$achDesc = $achievementEntry['Description'];
			$achMemAddr = htmlspecialchars( $achievementEntry['MemAddr'] );
			$achPoints = $achievementEntry['Points'];
			
			//$achCreated = $achievementEntry['DateCreated'];
			//$achModified = $achievementEntry['DateModified'];
			$achCreated = getNiceDate( strtotime( $achievementEntry['DateCreated'] ) );
			$achModified = getNiceDate( strtotime( $achievementEntry['DateModified'] ) );

			$achBadgeName = $achievementEntry['BadgeName'];
			$achDisplayOrder = $achievementEntry['DisplayOrder'];
      		$achBadgeFile = getenv('APP_STATIC_URL') . "/Badge/$achBadgeName" . ".png";

			echo "<tr>";
			
			echo "<td>$achID</td>";
			echo "<td><code>$achBadgeName</code><br/><img alt='' style='float:left;' src='$achBadgeFile' /></td>";
			echo "<td>$achTitle</td>";
			echo "<td>$achDesc</td>";
			//echo "<td>$achMemAddr</td>";
			echo "<td>$achPoints</td>";
			echo "<td><span class='smalldate'>$achCreated</span><br/><span class='smalldate'>$achModified</span></td>";
			if( $modifyOK )
				echo "<td><input class='displayorderedit' id='ach_$achID' type='text' value='$achDisplayOrder' onchange=\"UpdateDisplayOrder('$user', 'ach_$achID')\" size='3' /></td>";
			else
				echo "<td>$achDisplayOrder</td>";	//	Just remove the input
			
			echo "</tr>";
			echo "<tr>";
			echo "<td><b>Code:</b></td>";
			echo "<td colspan='6' style='padding: 10px; word-break:break-all;'>";
			echo "<code style='word-break:break-all;'>$achMemAddr</code>";
			echo "</td>";
			echo "</tr>";
		}
		
		echo "</tbody></table>";
	}
	else
	{
		echo "<h3>Pick a game to modify:</h3>";
		
		$lastConsole = 'NULL';
		foreach( $gamesList as $gameEntry )
		{
			$gameID = $gameEntry['ID'];
			$gameTitle = $gameEntry['Title'];
			$console = $gameEntry['ConsoleName'];
			
			if( $lastConsole == 'NULL' )
			{
				echo "$console:<select class='gameselector' onchange='window.location = \"/achievementinspector.php?g=\" + this.options[this.selectedIndex].value'><option>--</option>";
				$lastConsole = $console;
			}
			else if( $lastConsole !== $console )
			{
				echo "</select><br/>$console:<select class='gameselector' onchange='window.location = \"/achievementinspector.php?g=\" + this.options[this.selectedIndex].value'><option>--</option>";
				$lastConsole = $console;
			}
			
			echo "<option value='$gameID'>$gameTitle</option>";
			echo "<a href=\"/achievementinspector.php?g=$gameID\">$gameTitle</a><br/>";
			
			
		}
		echo "</select>";
	}
	
	echo "</div>";
	
	echo "<div id='rightcontainer'>";
	
	if( count( $codeNotes ) > 0 )
		RenderCodeNotes( $codeNotes );
		
	echo "</div>";
	?>
</div>

<?php RenderFooter(); ?>	

</body>
</html>
