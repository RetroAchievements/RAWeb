<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';

	//echo "Under development. Please wait! (30th Oct 2017)";
	//return;

	$allowNewPasswordEntry = FALSE;
	
	$user = seekGET('u');
	$passResetToken = seekGET('t');
	if( isset( $passResetToken ) && isset( $user ) )
	{
		if( IsValidPasswordResetToken( $user, $passResetToken ) )
		{
			$allowNewPasswordEntry = TRUE;
		}
	}
	
	$pageTitle = "Password Reset";
	$errorCode = seekGET('e');
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
</head>

<body>
<?php RenderTitleBar( $user, 0, 0, 0, $errorCode ); ?>
<?php RenderToolbar( $user, 0 ); ?>

<div id="mainpage">
	<div id="passwordreset" class="left">
		
		<?php
		echo "<div class='navpath'>";
		echo "<b>$pageTitle</b></a>";
		echo "</div>";
		
		echo "<h2 class='longheader'>$pageTitle</h2>";

		if( $allowNewPasswordEntry == NULL )
		{
			//	Request username for password reset:
			echo "<h4 class='longheader'>Enter username for password reset:</h2>";
			
			echo "<div class='longer'>";
			echo "<form action='/requestresetpassword.php' method='post'>";
			echo "<input type='text' name='u' value='' />";
			echo "&nbsp;&nbsp;";
			echo "<input type='submit' value='Request Reset' />";
			echo "</form>";
			echo "</div>";
		}
		else
		{
			//	Enter new password for this user:
			echo "<h4 class='longheader'>Enter new Password for $user:</h4>";
			
			echo "<div class='longer'>";
			echo "<form action='/requestchangepassword.php' method='post'>";
			echo "<input type='password' name='x' size='42' />&nbsp;";
			echo "<input type='password' name='y' size='42' />&nbsp;";
			echo "<input type='hidden' name='t' value='$passResetToken' />";
			echo "<input type='hidden' name='u' value='$user' />";
			echo "&nbsp;&nbsp;";
			echo "<input type='submit' value='Change Password' />";
			echo "</form>";
			echo "</div>";
		}
		?>
		
		<br/>
	</div> 
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

