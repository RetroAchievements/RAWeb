<?php
	require_once __DIR__ . '/../lib/bootstrap.php';

	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
	{
		if( getAccountDetails( $user, $userDetails ) == FALSE )
		{
			//	Immediate redirect if we cannot validate user!
			header( "Location: " . getenv('APP_URL') . "?e=accountissue" );
			exit;
		}
	}
	else
	{
		//	Immediate redirect if we cannot validate cookie!
		header( "Location: " . getenv('APP_URL') . "?e=notloggedin" );
		exit;
	}
	
	$errorCode = seekGET( 'e' );
	
	$points = $userDetails['RAPoints'];
	$fbUser = $userDetails['fbUser'];
	$fbPrefs = $userDetails['fbPrefs'];
	$emailAddr = $userDetails['EmailAddress'];
	
	$pageTitle = "Manage User Pic";
	
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<script type='text/javascript'>
		$(document).ready( function() {
			user = RA_ReadCookie('RA_User');
			d = new Date();
			$("#userpic").attr( "src", "/UserPic/" + user + ".png?" + d.getTime() );
			$("#userpiccopy").attr( "src", "/UserPic/" + user + ".png?" + d.getTime() );
		});
	</script>
	<?php RenderGoogleTracking(); ?>
</head>
<body>

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
	<div id="aboutme" class="left">
	
</div>
	
<?php RenderFooter(); ?>

</body>
</html>
