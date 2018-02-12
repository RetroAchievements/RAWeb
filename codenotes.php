<?php
	require_once('db.inc.php');
	
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

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id='mainpage'>

<div style='padding:0px 10px;'>

<?php echo "Game: " . GetGameAndTooltipDiv( $gameData['ID'], $gameData['Title'], $gameData['ImageIcon'], $gameData['ConsoleName'] ); ?>

<?php
if( isset( $gameData ) && isset( $user ) && $permissions >= 3 )
{
	RenderCodeNotes( $codeNotes );
}
?>

</div>
</div>

<?php RenderFooter(); ?>

</body>
</html>

