<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	//require_once('recaptchalib.php');

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	
	$errorCode = seekGET( 'e' );
	
	$pageTitle = "Create Account";
	RenderDocType();
?>
<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
	<script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
	<div id="achievement" class="left">
		<h3>create account</h3>
		<div class="infobox">
			<form method=post action="requestcreateuser.php">
				<table class='paddedtable' >
					<tbody>
					<tr><td class="label"><label for="u">Username:</label></td>
					<td><div><input type="text" class="inputtext" id="loginbox" name='u' size='25' /></div></td></tr>
					<tr><td class="label"><label for="e">Your Email:</label></td>
					<td><div><input type="text" class="inputtext" id="loginbox" name='e' size='50' /></div></td></tr>
					<tr><td class="label"><label for="f">Re-enter Email:</label></td>
					<td><div><input type="text" class="inputtext" id="loginbox" name='f' size='50' /></div></td></tr>
					<tr><td class="label"><label for="p">New Password:</label></td>
					<td><div><input type="password" class="inputtext" id="loginbox" name='p' size='25' value="" /></div></td></tr>
					<tr>
					<td class="label"><label for="captcha">Are you a robot?</label></td>
					<td><div class="field_container">

					<div class="g-recaptcha" data-sitekey="6Lc7pu4SAAAAAG8piZ7lTwb2LQ4BkKT20Tj9OUlb"></div>

					<div>
					<?php /*echo recaptcha_get_html( "6Lc7pu4SAAAAAG8piZ7lTwb2LQ4BkKT20Tj9OUlb" );*/ ?>
					</div>

					</td>
					</tr>

					<td class="label"></td>
					<td>
						<div class="field_container">
							By clicking 'Create User', you agree to the Terms and Conditions below.</br>
							<input value="Create User" name='submit' type='submit' size='37'>
							<br/>
						</div>
					</td>

					<tr><td colspan="2">
						<p>Terms and Conditions:</br>
						<small>Your email will be stored securely and will <i>never</i> be sold to a third party. By default you will receive an email notification if
						you receive a new friend request, activity comment, or a direct private message. You can unsubscribe from these at any time on your My Settings page.<br/>
						By signing up you will be allowed to use the forums, chat and other communications available on the site. You agree not to post offensive messages
						and you accept that you can and will be banned from the site for any duration or permanently at the administrators discretion. <br/>
						Finally, you also agree that RetroAchievements.org is not responsible in any way for any personal loss you may sustain.</small></p>
						</td></tr>

					</tbody>
				</table>
			</form>
		</div>
	</div>
</div>

<?php RenderFooter(); ?>

</body>
</html>
