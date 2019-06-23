<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	RenderDocType();
	
	$gameID = seekGET( 'g', 1 );
	$gameData = getGameData( $gameID );
	getCodeNotes( $gameID, $codeNotes );
	
	$errorCode = seekGET('e');
?>

<head>	
	
<?php
	RenderSharedHeader( $user );
?>

</head>

<body>
<script type='text/javascript' src='/js/wz_tooltip.js'></script>
<script type='text/javascript' src="js/ping_chat.js"></script>

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id='mainpage'>

<div style='padding:0px 10px;'>

<?php echo "Game: " . GetGameAndTooltipDiv( $gameData['ID'], $gameData['Title'], $gameData['ImageIcon'], $gameData['ConsoleName'] ); ?>

<?php
if( isset( $gameData ) && isset( $user ) && $permissions >= 2 )
{
	RenderCodeNotes( $codeNotes );
}
?>

</div>
</div>

<?php RenderFooter(); ?>

</body>
</html>

