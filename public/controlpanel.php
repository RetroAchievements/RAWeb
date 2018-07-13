<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
	{
		if( getAccountDetails( $user, $userDetails ) == FALSE )
		{
			//	Immediate redirect if we cannot validate user!
			header( "Location: " . APP_URL . "?e=accountissue" );
			exit;
		}
	}
	else
	{
		//	Immediate redirect if we cannot validate cookie!
		header( "Location: " . APP_URL . "?e=notloggedin" );
		exit;
	}
	
	//if( $user == "Scott" )
	//{
	//	log_email("Hi Scott! Testing!");
	//	echo "Hi Scott!";
	//}
	
	$points = $userDetails['RAPoints'];
	$fbUser = $userDetails['fbUser'];
	$fbPrefs = $userDetails['fbPrefs'];
	$websitePrefs = $userDetails['websitePrefs'];
	$emailAddr = $userDetails['EmailAddress'];
	$permissions = $userDetails['Permissions'];
	$contribCount = $userDetails['ContribCount'];
	$contribYield = $userDetails['ContribYield'];
    $userWallActive = $userDetails['UserWallActive'];
	$apiKey = $userDetails['APIKey'];
	$userMotto = htmlspecialchars( $userDetails['Motto'] );
	
	$pageTitle = "My Settings";
	
	$cookie = RA_ReadCookie( 'RA_Cookie' );
	$errorCode = seekGET( 'e' );
	
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
	<script type='text/javascript' src="/js/all.js"></script>
	<script>
	function GetAllResettableGamesList()
	{
		$( '#resetgameselector' ).empty();
		
		var posting = $.post( "/requestuserplayedgames.php", { u: '<?php echo $user; ?>' } );
		posting.done( OnGetAllResettableGamesList );
		
		$( '#loadingiconreset' ).attr( 'src', '<?php echo getenv('APP_STATIC_URL') ?>/Images/loading.gif' ).fadeTo( 100, 1.0 );
	}
	
	function OnGetAllResettableGamesList( data )
	{
		if( data !== "ERROR3" )
		{
			//alert( data );
			
			var htmlToAdd = "<select id='resetgameselector' onchange=\"ResetFetchAwarded()\" >"
			htmlToAdd += "<option>--</option>";
			
			var gameList = JSON.parse( data );
			
			for( var i = 0; i < gameList.length; ++i )
			{
				var object = gameList[i];
				
				var nextID = object.ID;
				var console = object.ConsoleName;
				var title = object.GameTitle;
				var numAw = object.NumAwarded;
				var numPoss = object.NumPossible;
				
				htmlToAdd += "<option value='" + nextID + "'>" + title + " (" + console + ") (" + numAw + " / " + numPoss + " won)";
			}
						
			htmlToAdd += "</select>";
			
			$( '#resetgameselector' ).html( htmlToAdd );
		
			$( '#loadingiconreset' ).attr( 'src', '<?php echo getenv('APP_STATIC_URL') ?>/Images/tick.png' ).delay( 750 ).fadeTo( "slow", 0.0 );
		}
		
		ResetFetchAwarded();
	}
	
	function ResetFetchAwarded()
	{
		var gameID = parseInt( $( '#resetgameselector :selected' ).val() );
		if( gameID > 0 )
		{
			var posting = $.post( "/requestunlockssite.php", { u: '<?php echo $user; ?>', g: gameID } );
			posting.done( onFetchComplete );
			$( '#resetachievementscontainer' ).empty();
			$( '#warning' ).html( 'Status: Updating...' );
		}
	}

	function onFetchComplete( data )
	{
		if( data.substr( 0, 2 ) !== "OK" )
		{
			$( '#warning' ).html( 'Status: Errors...' );
			alert( data );
		}
		else
		{
			$( '#warning' ).html( 'Status: OK...' );
			
			var achList = data.substr( 3 );
			var achData = achList.split( "::" );
			
			if( achData.length > 0 && achData[0].length > 0 )
			{
				//alert( achData );
				$( '#resetachievementscontainer' ).append( "<option value='9999999' >All achievements for this game</option>" );
			}
			
			for( var index = 0; index < achData.length; ++index )
			{
				var nextData = achData[index];
				var dataChunks = nextData.split( "_:_" );
				
				//alert( dataChunks );
				if( dataChunks.length < 2 )
					continue;
				
				var achTitle = dataChunks[0];
				var achID = dataChunks[1];
				if( achID[0] == 'h' )
				{
					//	Hardcore:
					achTitle = achTitle + " (Hardcore)";
					$( '#resetachievementscontainer' ).append( "<option value='" + achID + "'>" + achTitle + "</option>" );
				}
				else
				{
					//	Casual:
					$( '#resetachievementscontainer' ).append( "<option value='" + achID + "'>" + achTitle + "</option>" );
				}
			}
			
		}
	}
	
	function ResetProgressForSelection()
	{
		var achID = $( '#resetachievementscontainer :selected' ).val();
		
		var isHardcore = 0;
		if( achID[0] == 'h' )
		{
			achID = achID.substr( 1 );
			isHardcore = 1;
		}
			
		if( achID == 9999999 )
		{
			//	'All Achievements' selected: reset this game entirely!
			var gameID = $( '#resetgameselector :selected' ).val();
			//alert( "Game ID is " + gameID );
			var posting = $.post( "/requestresetachievements.php", { u: '<?php echo $user; ?>', g: gameID } );
			posting.done( onResetComplete );
		}
		else if( achID > 0 )
		{
			//	Particular achievement selected: reset just this achievement
				
			//alert( "Ach ID is " + achID );
			//alert( "isHardcore is " + isHardcore );
			var posting = $.post( "/requestresetachievements.php", { u: '<?php echo $user; ?>', a: achID, h: isHardcore } );
			posting.done( onResetComplete );
		}
		
		$( '#warning' ).html( 'Status: Updating...' );
		$( '#loadingiconreset' ).attr( 'src', '<?php echo getenv('APP_STATIC_URL') ?>/Images/loading.gif' ).fadeTo( 100, 1.0 );
	}
	
	function onResetComplete( data )
	{
		if( data.substr( 0, 2 ) !== "OK" )
		{
			alert( data );
		}
		else
		{
			$( '#loadingiconreset' ).attr( 'src', '<?php echo getenv('APP_STATIC_URL') ?>/Images/tick.png' ).delay( 750 ).fadeTo( "slow", 0.0 );
			//window.location = '/controlpanel.php?e=resetok';
			if( $( '#resetachievementscontainer' ).children('option').length > 2 )
				ResetFetchAwarded();			//	Just reset ach. list
			else
				GetAllResettableGamesList();	//	last ach reset: fetch new list!
			return false;
		}
	}
	
	function DoChangeUserPrefs()
	{
		var newUserPrefs = 0;
		for( i = 0; i < 16; ++i )
		{
			var checkbox = document.getElementById( "UserPref" + i );
			if( checkbox != null && checkbox.checked )
				newUserPrefs += (1<<i);
		}
		
		$('#loadingicon').attr( 'src', '<?php echo getenv('APP_STATIC_URL') ?>/Images/loading.gif' ).fadeTo( 100, 1.0 );
		var posting = $.post( "/requestchangesiteprefs.php", { u: '<?php echo $user; ?>', p: newUserPrefs } );
		posting.done( OnChangeUserPrefs );
	}

	function OnChangeUserPrefs( object )
	{
		//console.log( object )
		$('#loadingicon').attr( 'src', '<?php echo getenv('APP_STATIC_URL') ?>/Images/tick.png' ).delay( 750 ).fadeTo( "slow", 0.0 );
	}
	
	function DoChangeFBUserPrefs()
	{
		var newUserPrefs = 0;
		for( i = 0; i < 16; ++i )
		{
			var checkbox = document.getElementById( "FBUserPref" + i );
			if( checkbox != null && checkbox.checked )
				newUserPrefs += (1<<i);
		}
		
		$('#loadingiconfb').attr( 'src', '<?php echo getenv('APP_STATIC_URL') ?>/Images/loading.gif' ).fadeTo( 100, 1.0 );
		var posting = $.post( "/requestchangefb.php", { u: '<?php echo $user; ?>', p: newUserPrefs } );
		posting.done( OnChangeFBUserPrefs );
	}

	function OnChangeFBUserPrefs( object )
	{
		console.log( object )
		if( object == 'OK' )
			$('#loadingiconfb').attr( 'src', '<?php echo getenv('APP_STATIC_URL') ?>/Images/tick.png' ).delay( 750 ).fadeTo( "slow", 0.0 );
	}
	
	function UploadNewAvatar()
	{
		//	New file
  		var photo = document.getElementById("uploadimagefile");
		var file = photo.files[0];
		
		var reader = new FileReader();
		reader.onload = function() {
	
			$('#loadingiconavatar').fadeTo( 100, 1.0 );
			$.post( 'doupload.php', { r: "uploaduserpic", f: file.name.split('.').pop(), i: reader.result }, onUploadImageComplete );
		}

		reader.readAsDataURL( file );
		return false;
	}

	function onUploadImageComplete( data )
	{
		$('#loadingiconavatar').fadeTo( 100, 0.0 );
	               var response = JSON.parse( data );
		//if( data.substr( 0, 2 ) == "OK" )
                              if( true )
		{
			var d = new Date();
			$('.userpic').attr( 'src', '/UserPic/<?php echo $user; ?>' + '.png?' + d.getTime() );
		}
		else
		{
			alert( data );
		}
	}


	GetAllResettableGamesList();
	
	window.fbAsyncInit = function() {
		FB.init({
			appId      : '490904194261313',
			status     : true, // check login status
			cookie     : true, // enable cookies to allow the server to access the session
			xfbml      : true  // parse XFBML
		});

		// Here we subscribe to the auth.authResponseChange JavaScript event. This event is fired
		// for any authentication related change, such as login, logout or session refresh. This means that
		// whenever someone who was previously logged out tries to log in again, the correct case below 
		// will be handled. 
		FB.Event.subscribe('auth.authResponseChange', function(response) {
			//alert( response.status );

			// Here we specify what we do with the response anytime this event occurs. 
			if (response.status === 'connected')
			{
				FB.api('/me', function(response)
				{
					var postingupdate = $.post( '/requestassociatefb.php', { u: '<?php echo $user; ?>', f: response.id } );
					postingupdate.done( function(data)
						{
							console.log('FB associate: ' + data + '.');
							
							<?php 
							if( $fbUser == 0 )	//	Refresh if it was 0
								echo "window.location = '/controlpanel.php?e=associateok'";
							?>
						}
					);
				});
				
			} else if (response.status === 'not_authorized') {
			  // In this case, the person is logged into Facebook, but not into the app, so we call
			  // FB.login() to prompt them to do so. 
			  // In real-life usage, you wouldn't want to immediately prompt someone to login 
			  // like this, for two reasons:
			  // (1) JavaScript created popup windows are blocked by most browsers unless they 
			  // result from direct interaction from people using the app (such as a mouse click)
			  // (2) it is a bad experience to be continually prompted to login upon page load.
				FB.login(function(response) {
				// handle the response
				}, {scope: 'publish_actions'});
				

			} else {
			  // In this case, the person is not logged into Facebook, so we call the login() 
			  // function to prompt them to do so. Note that at this stage there is no indication
			  // of whether they are logged into the app. If they aren't then they'll see the Login
			  // dialog right after they log in to Facebook. 
			  // The same caveats as above apply to the FB.login() call here.
				FB.login(function(response) {
				// handle the response
				}, {scope: 'publish_actions'});
			}
		});
		
	};

	// Load the SDK asynchronously
	(function(d){
		var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement('script'); js.id = id; js.async = true;
		js.src = "//connect.facebook.net/en_US/all.js";
		ref.parentNode.insertBefore(js, ref);
	}(document));

	</script>
</head>
<body>

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
<div id="leftcontainer">

	<?php RenderErrorCodeWarning( 'left', $errorCode ); ?>
	
	<div class='component' >
	<h2>User Details</h2>
	<?php	
	//	Render user panel
	echo "<p style='min-height:62px'>";
	echo "<img class='userpic' src='/UserPic/$user.png' alt='$user' align='right' width='64' height='64'>";
	echo "<strong><a href='/User/$user'>$user</a></strong> ($points points)<br/>";		
	echo "Account: ($permissions) " . PermissionsToString( $permissions ) . "<br/>";
	if( isset( $userMotto ) && strlen( $userMotto ) > 1)
		echo "<span class='usermotto'>$userMotto</span><br/>";	
	echo "</p>";
	
	if( $permissions == \RA\Permissions::Unregistered )
		echo "<div id='warning'>Warning: Email address not confirmed. Please check your inbox or spam folders, or click <a href='/requestresendactivationemail.php?u=$user'>here</a> to resend your activation email!</div>";
	?>
	
	</div>
	<?php
	if( $permissions > 0 )
	{
		echo "<div class='component'>";
		echo "<h3>Account Privileges</h3>";
		echo "<table><tbody>";
		
		if( $permissions >= 1 )
		{
			$userMottoString = isset( $userMotto ) ? $userMotto : "";
			echo "<tr>";
			echo "<td>User Motto:</td>";
			echo "<td>";
			echo "<form method='POST' action='requestsubmitusermotto.php'>";
			echo "<input type='text' name='m' value=\"$userMottoString\" size='50' maxlength='49' id='usermottoinput'/>";
			echo "<input type='hidden' name='u' VALUE='$user'>";
			echo "<input type='hidden' name='c' VALUE='$cookie'>";
			echo "&nbsp;<input value='Set Motto' name='submit' type='submit' size='37' />";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
			
			echo "<tr>";
			echo "<td>";
			echo "<a href='/APIDemo.php'>API Key:</a>";
			echo "</td>";
			echo "<td>";
			echo "<input size='60' readonly type='text' value='$apiKey' /></td>";
			echo "</td>";
			echo "</tr>";
		}
		
		if( $permissions >= 1 )
		{
			echo "<tr><td>Twitch.tv streamkey:</td><td><input size='60' readonly type='text' value='live_46798798_5tO2CCgggTMoi5458BLKUADECNpOrq' /></td></tr>";
		}
        
		if( $permissions >= 0 )
		{	
			echo "<tr>";
			echo "<td>";
            echo "Allow Comments on my User Wall: ";
			echo "</td>";
			echo "<td>";
			echo "<form method='POST' action='requestsubmituserprefs.php'>";
            $checkedStr = ( $userWallActive == 1 ) ? "checked" : "";
			echo "<input type='checkbox' name='v' value=\"1\" id='userwallactive' $checkedStr/>";
			echo "<input type='hidden' name='u' value='$user'>";
			echo "<input type='hidden' name='c' value='$cookie'>";
			echo "<input type='hidden' name='t' value='wall'>";
			echo "&nbsp;<input value='Save' name='submit' type='submit' size='37' />";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
            
            
			echo "<tr>";
			echo "<td>";
            echo "Remove all comments from my User Wall: ";
			echo "</td>";
			echo "<td>";
			echo "<form method='POST' action='requestsubmituserprefs.php'>";
			echo "<input type='hidden' name='u' value='$user'>";
			echo "<input type='hidden' name='c' value='$cookie'>";
			echo "<input type='hidden' name='t' value='cleanwall'>";
			echo "&nbsp;<input value='Delete All Comments' name='submit' type='submit' size='37' />";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
		}
		
		echo "</tbody></table>";
		echo "</div>";
	}
	?>
	
	<div class='component'>
	<h3>Facebook</h3>
	
	<?php if( $fbUser !== "0" ) 
	{ 
		$loggedIn = RenderFBDialog( $fbUser, $fbRealName, $fbURL, $user );
		if( $fbUser !== 0 )
		{
			echo "<image class='rightfloat' src='http://graph.facebook.com/$fbUser/picture?type=square' width='50' height='50'>";
			echo "Logged in as: ";
			echo "<a href='$fbURL'>$fbRealName</a><br/>";
		}
		
		if( $errorCode == 'associateOK' )
		{
			echo "<div id=\"warning\">Facebook associated OK, $fbRealName! Please confirm below what you would prefer to have cross-posted to your facebook wall:</div>";
		}
		
		?>
		<br/>
		<h4>Facebook Preferences</h4>
		When would you like RetroAchievements to automatically post on your Facebook wall?
		<table><tbody>
		<!--<tr><th>Action</th><th>Post on Facebook?</th></tr>-->
		<tr>
			<td>When I earn achievements:</td>
			<td><input id='FBUserPref0' type="checkbox" onchange='DoChangeFBUserPrefs(); return false;' value="1" <?php if( BitSet( $fbPrefs, FBUserPref::PostFBOn_EarnAchievement ) ) { echo "checked"; } ?> ></td>
		</tr>
		<tr class='alt'>
			<td>When I fully complete a game:</td>
			<td><input id='FBUserPref1' type="checkbox" onchange='DoChangeFBUserPrefs(); return false;' value="1" <?php if( BitSet( $fbPrefs, FBUserPref::PostFBOn_CompleteGame ) ) { echo "checked"; } ?> ></td>
		</tr>
		<tr>
			<td>When I upload an achievement:</td>
			<td><input id='FBUserPref2' type="checkbox" onchange='DoChangeFBUserPrefs(); return false;' value="1" <?php if( BitSet( $fbPrefs, FBUserPref::PostFBOn_UploadAchievement ) ) { echo "checked"; } ?> ></td>
		</tr>
		
		</tbody></table>
		
		<img id='loadingiconfb' style='opacity: 0; float: right;' src='<?php echo getenv('APP_STATIC_URL') ?>/Images/loading.gif' width='16' height='16' alt='loading icon' />
		
		<br/>
		<h4>Unlink Facebook</h4>
		Click <a href="/requestremovefb.php?u=<?php echo $user;?>">here</a> to remove Facebook from your RetroAchievements account. 
		Please note you will also need to remove permissions from within Facebook to fully disassociate this app, 
		by visiting <a href="http://www.facebook.com/settings?tab=applications">this page</a> on Facebook.
		<br/><br/>
	<?php 
		} 
		else 
		{
			echo "<fb:login-button show-faces='false' width='200' max-rows='1' data-perms='publish_actions'></fb:login-button>";
			//RenderFBLoginPrompt();
			//echo "<div class='fb-login-button' scope='publish_stream;publish_actions'>Login with Facebook</div>";
			echo "<br/>";
		}
	?>
	
	</div>

	<div class='component'>
	<h3>Notifications</h3>
	When would you like to be notified?
	
	<table class='smalltable'><tbody>
	<tr><th>Event</th><th>Email Me</th><th>Site Msg</th></tr>
	<tr>
	
		<td>If someone comments on my activity:</td>
		<td><input id='UserPref0'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::EmailOn_ActivityComment ) ) { echo "checked"; } ?> ></td>
		<td><input id='UserPref8'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::SiteMsgOn_ActivityComment ) ) echo "checked"; ?> ></td>
	</tr>
	<tr class='alt'>
		<td>If someone comments on an achievement I created:</td>
		<td><input id='UserPref1'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::EmailOn_AchievementComment ) ) echo "checked"; ?> ></td>
		<td><input id='UserPref9'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::SiteMsgOn_AchievementComment ) ) echo "checked"; ?> ></td>
	</tr>
	<tr>
		<td>If someone comments on my user wall:</td>
		<td><input id='UserPref2'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::EmailOn_UserWallComment ) ) echo "checked"; ?> ></td>
		<td><input id='UserPref10' type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::SiteMsgOn_UserWallComment ) ) echo "checked"; ?> ></td>
	</tr>
	<tr class='alt'>
		<td>If someone comments on a forum topic I'm involved in:</td>
		<td><input id='UserPref3'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::EmailOn_ForumReply ) ) echo "checked"; ?> ></td>
		<td><input id='UserPref11' type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::SiteMsgOn_ForumReply ) ) echo "checked"; ?> ></td>
	</tr>
	<tr>
		<td>If someone adds me as a friend:</td>
		<td><input id='UserPref4'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::EmailOn_AddFriend ) ) echo "checked"; ?> ></td>
		<td><input id='UserPref12' type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::SiteMsgOn_AddFriend ) ) echo "checked"; ?> ></td>
	</tr>
	<tr class='alt'>
		<td>If someone sends me a private message:</td>
		<td><input id='UserPref5'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::EmailOn_PrivateMessage ) ) echo "checked"; ?> ></td>
		<td><input id='UserPref13' type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" disabled checked ></td>
	</tr>
	<tr>
		<td>With the weekly RA Newsletter:</td>
		<td><input id='UserPref6'  type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" <?php if( BitSet( $websitePrefs, UserPref::EmailOn_Newsletter ) ) echo "checked"; ?> ></td>
		<td><input id='UserPref14' type="checkbox" onchange='DoChangeUserPrefs(); return false;' value="1" disabled ></td>
	</tr>
	
	</tbody></table>
	
	<img id='loadingicon' style='opacity: 0; float: right;' src='<?php echo getenv('APP_STATIC_URL') ?>/Images/loading.gif' width='16' height='16' alt='loading icon' />
	
	</div>

	<div class='component'>
	<h3>Change Password</h3>
	
	<?php
	if( $errorCode == 'baddata' || $errorCode == 'generalerror' )
		echo "<div id=\"warning\">Info: Errors changing your password. Please check and try again!</div>";
	else if( $errorCode == 'badnewpass' )
		echo "<div id=\"warning\">Info: Errors changing your password, passwords too short!</div>";
	else if( $errorCode == 'passinequal' )
		echo "<div id=\"warning\">Info: Errors changing your password, new passwords were not identical!</div>";
	else if( $errorCode == 'badpassold' )
		echo "<div id=\"warning\">Info: Errors changing your password, old password was incorrect!</div>";
	else if( $errorCode == 'changepassok' )
		echo "<div id=\"warning\">Info: Password changed OK!</div>";
	?>
	
	<form method='post' action='requestchangepassword.php'>
	
	<table id='controlpanelinput'><tbody>
	<tr>
	<td class='firstrow'>Old Password:</td>
	<td><input size='22' type='password' name="p" /></td>
	</tr>
	<tr>
	<td class='firstrow'>New Password: </label></td>		
	<td><input size='22' type='password' name="x" /></td>
	</tr>
	<tr>
	<td class='firstrow'>New Password again: </label></td>
	<td><input size='22' type='password' name="y" /></td>
	</tr>
	
	<tr>
	<td></td><td><input value="Change Password" name='submit' type='submit' size='37'></td>
	</tr>
	
	<input type="hidden" name="u" value="<?php echo $user; ?>">
	
	</tbody></table>
	</form>
		
	</div>

	<div class='component'>
	<h3>Change Email Address</h3>
	
	<?php
	if( $errorCode == 'e_baddata' || $errorCode == 'e_generalerror' )
		echo "<div id=\"warning\">Info: Errors changing your email address. Please check and try again!</div>";
	else if( $errorCode == 'e_badnewemail' )
		echo "<div id=\"warning\">Info: Errors changing your email address, the new email doesn't appear to be valid!</div>";
	else if( $errorCode == 'e_notmatch' )
		echo "<div id=\"warning\">Info: Errors changing your email address, new emails were not identical!</div>";
	else if( $errorCode == 'e_badcredentials' )
		echo "<div id=\"warning\">Info: Errors changing your email address, session invalid. Please log out and back in, and try again!</div>";
	else if( $errorCode == 'e_changeok' )
		echo "<div id=\"warning\">Info: Email address changed OK!</div>";
	?>
	
	<form method='post' action='requestchangeemailaddress.php'>
	<table id='controlpanelinput'><tbody>
	<tr>
	<td class='firstrow'>Old Email Address:</td>
	<td><div class="field_container"><input type="text" class="inputtext" size='30' disabled VALUE="<?php echo $emailAddr; ?>" /></div></td>
	</tr>
	<tr>
	<td class='firstrow'>New Email Address:</td>
	<td><div class="field_container"><input type="text" class="inputtext" name="e" size='30' /></div></td>
	</tr>
	<tr>
	<td class='firstrow'>New Email Address again:</td>
	<td><div class="field_container"><input type="text" class="inputtext" name="f" size='30' /></div></td>
	</tr>
	
	<tr>
	<td></td><td><input value="Change Email Address" type='submit' size='37'></td>
	</tr>
	
	<input TYPE="hidden" NAME="u" VALUE="<?php echo $user; ?>">
	<input TYPE="hidden" NAME="c" VALUE="<?php echo $cookie; ?>">
	
	</tbody></table>
	</form>
		
	</div>

	<div class='component'>
	<h3>Reset Game Progress</h3>
	<?php
		echo "Reset all achievements for a certain game:</br>";
		
		echo "<select id='resetgameselector' onchange=\"ResetFetchAwarded()\" >";
		echo "<option>--</option>";
		echo "</select>";
		echo "<div id='resetachievementscontrol'>";
		echo "<select id='resetachievementscontainer'></select>";	//	Filled by JS
		echo "</div>";
		
		echo "<input value='Reset Progress for selection' type='submit' onclick=\"ResetProgressForSelection()\" >";
		echo "</form>";
		
		echo "<img id='loadingiconreset' style='opacity: 0; float: right;' src='" . getenv('APP_STATIC_URL') . "/Images/loading.gif' width='16' height='16' alt='loading icon' />";
		
		echo "<br/>";
	?>
	</div>

	<div class='component'>
	<h3>Reset All Achievements</h3>
	Enter password to confirm! Please note: this is <b>not</b> reversible!
	<form method=post action="requestresetachievements.php">
	<INPUT TYPE="hidden" NAME="u" VALUE="<?php echo $user; ?>">
	<INPUT TYPE="password" NAME="p" VALUE="">
	<INPUT value="Permanently Reset Achievements!" type='submit' size='67'>
	</form>
	</div>
	

</div>

	<div id='rightcontainer'>
	
	<div class='component'>
	<h3>Request Score Recalculation</h3>
	<form method=post action="requestscorerecalculation.php">
	<input TYPE="hidden" NAME="u" VALUE="<?php echo $user; ?>">
	If you feel your score is inaccurate due to point values varying during achievement development, you can request a recalculation by using the button below.<br/><br/>
	<input value="Recalculate My Score" type='submit' size='37'>
	</form>
	</div>
	
	<div class='component' >
	<h2>Change User Pic</h2>
	
	New image should be less than 1MB, png/jpeg/gif supported.</br>
	</br>
	<input style='padding: 4px;' type='file' name='file' id='uploadimagefile' onchange='return UploadNewAvatar();' />
	<img id='loadingiconavatar' style='opacity: 0; float: right;' src='<?php echo getenv('APP_STATIC_URL') ?>/Images/loading.gif' width='16' height='16' alt='loading icon' />
		
	
	</div>
	
</div>

<?php RenderFooter(); ?>

</body>
</html>
