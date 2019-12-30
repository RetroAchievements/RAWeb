<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	//include("XML/Serializer.php");
	//require 'vendor/autoload.php';

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

	$consoleID = seekGET( 'c', 1 );
	//getGamesList( $consoleID, $gameData );
	$gameData = getMostPopularGames( 0, 5, 0 );
	
	$gameListCSV = "";
	foreach( $gameData as $nextGame )
		$gameListCSV .= $nextGame['ID'] . ", ";
		
	$gameListCSV .= "0";
	
	getUserProgress( $user, $gameListCSV, $userProgress );
	
	$errorCode = seekGET( 'e' );
	
	$gameRatingData = getGamesByRating( 0, 999 );
	
	RenderHtmlStart();
?>

<head>

<?php
	RenderSharedHeader( $user );
	RenderTitleTag( "Test Page", $user );
	RenderGoogleTracking();
?>

<style>
	.gameicontest { display: inline };
</style>

<script>
	function OnHover()
	{
		console.log('OnHover');
		//$(this).fadeIn();
		$(this).fadeTo("fast", 1.0);
	}
	
	function OnUnHover()
	{
		console.log('OnUnHover');
		$(this).fadeTo("fast", 0.6);
	}
		
	$( document ).ready(function() {
		$('.gameicontest').fadeTo( 0.0, 0.6 );
		$('.gameicontest').hover( OnHover, OnUnHover );
	} );

</script>

</head>

<body>

<?php
	RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions );
	RenderToolbar( $user, $permissions );
?>

<div id='mainpage'>

<div id='leftcontainer'>
<?php
	echo "<div>";
	foreach( $gameData as $nextGame )
	{
		$gameTitle = $nextGame['Title'];
		$gameID = $nextGame['ID'];
		$consoleName = $nextGame['ConsoleName'];
		$gameIcon = $nextGame['ImageIcon'];
		
		if( strcmp( $gameIcon, "/Images/000001.png" ) != 0 )//	Ignore unset game icons
		{
			echo "<div class='gameicontest' id='gameid$gameID'>";
			echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, TRUE, 48 );
			echo "</div>";
		}
		
		//var_dump( $gameRatingData );
	}

	echo "<h2>Most Voted</h2>";
	echo "<table><tbody>";
	
	echo "<tr>";
	echo "<th>Game</th>";
	echo "<th>Avg Vote</th>";
	echo "<th>Num Votes</th>";
	echo "</tr>";
	
	foreach( $gameRatingData as $nextItem )
	{
		echo "<tr>";
		
		echo "<td>";
		echo GetGameAndTooltipDiv( $nextItem['GameID'], $nextItem['GameTitle'], $nextItem['GameIcon'], $nextItem['ConsoleName'], FALSE, 64 );
		echo "</td>";
		echo "<td>";
		echo $nextItem['AvgVote'];
		echo "</td>";
		echo "<td>";
		echo $nextItem['NumVotes'];
		echo "</td>";
		
		echo "</tr>";
	}
	echo "</tbody></table>";
	echo "</div>";
?>	
</div>

<div id='rightcontainer'>
<?php
	//RenderStaticDataComponent( $staticData );
	//RenderRecentlyUploadedComponent( 10 );
?>	
</div>

</div>

<?php RenderFooter(); ?>

</body>
<?php RenderHtmlEnd(); ?>
