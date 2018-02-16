<?php
	require_once __DIR__ . '/../lib/bootstrap.php';

	if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Developer ) )
	{
		//	Immediate redirect if we cannot validate user!	//TBD: pass args?
		header( "Location: http://" . AT_HOST );
		exit;
	}

	$gameID = seekGET( 'g' );
	$errorCode = seekGET( 'e' );
	
	$achievementList = array();
	$gamesList = array();
	
	$gameIDSpecified = ( isset( $gameID ) && $gameID != 0 );
	if( $gameIDSpecified )
	{
		getGameMetadata( $gameID, $user, $achievementData, $gameData );
	}
	else
	{
		//	Immediate redirect: this is pointless otherwise!
		header( "Location: http://" . AT_HOST );
	}
	
	//var_dump( $gameData );
	$gameTitle = $gameData['Title'];
	$consoleName = $gameData['ConsoleName'];
	$consoleID = $gameData['ConsoleID'];
	$gameIcon = $gameData['ImageIcon'];
	
	$pageTitle = "Merge Game Entry ($consoleName)";
	
	$numGames = getGamesListWithNumAchievements( $consoleID, $gamesList, 0 );
	//var_dump( $gamesList );
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
	<div class='left'>
	
	<h2>Merging Game Entry</h2>

	<?php
	
	echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, FALSE, 96 );
	echo "</br></br>";
	echo " Merging game entry <a href='/Game/$gameID'>$gameTitle</a> for $consoleName with another entry for $consoleName.<br/>";
	echo "Please select an existing $consoleName game to merge this entry with:<br/><br/>";
	
	echo "<FORM method=post action='requestmergegameids.php'>";
	echo "<INPUT TYPE='hidden' NAME='u' VALUE='$user'>";
	echo "<INPUT TYPE='hidden' NAME='g' VALUE='$gameID'>";
	echo "<SELECT NAME='n'>";
	foreach( $gamesList as $gameEntry )
	{
		$nextGameTitle = $gameEntry['Title'];
		$nextGameID = $gameEntry['ID'];
		$nextGameNumCheevos = $gameEntry['NumAchievements'];
		echo "<option name='n' value='$nextGameID'>$nextGameTitle ($nextGameNumCheevos)</option>";
	}
	
	echo "</SELECT>";
	
	echo "&nbsp;<INPUT type='submit' value='Submit' />";
	echo "</FORM>";
	
	echo "<br/><div id='warning'><b>Warning:</b> PLEASE be careful with this tool. If in doubt, <a href='/createmessage.php?t=Scott&s=Attempt%20to%20Merge%20a%20title'>leave me a message</a> and I'll help sort it.</div>";
	?>
	<br/>
	</div>
</div>

<?php RenderFooter(); ?>	

</body>
</html>
