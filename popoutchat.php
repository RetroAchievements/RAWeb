<?php
	require_once('db.inc.php');
	
	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	RenderDocType();
?>

<head>	
	
<?php
	RenderSharedHeader( $user );
?>

</head>

<body onload="init_chat(50);"/>
<script type='text/javascript' src='/js/wz_tooltip.js'></script>
<script type='text/javascript' src="js/ping_chat.js"></script>

<div style='padding:0px 10px;'>

<?php RenderChat( $user, 420 ); ?>
	
</div>

</body>
</html>
