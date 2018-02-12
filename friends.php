<?php 
	require_once('db.inc.php');
	
	if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Unregistered ) )
	{
		if( getAccountDetails( $user, $userDetails ) == FALSE )
		{
			//	Immediate redirect if we cannot validate user!
			header( "Location: http://" . AT_HOST . "?e=accountissue" );
			exit;
		}
	}
	else
	{
		//	Immediate redirect if we cannot validate cookie!
		header( "Location: http://" . AT_HOST . "?e=notloggedin" );
		exit;
	}
	
	$cookie = $userDetails['cookie'];
	
	$friendsList = getFriendList( $user );
	
	$errorCode = seekGET( 'e' );
	$pageTitle = "Friends";
	
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
<div id="leftcontainer">
		<h2>Friends</h2>
		<?php
		if( !isset( $friendsList ) )
		{
			echo "You don't appear to have friends registered here yet. Why not leave a comment on the <a href='/forum.php'>forums</a> or <a href='/userList.php'>browse the user pages</a> to find someone to add to your friend list?<br/>";
		}
		else
		{
			echo "<table><tbody>";
			echo "<tr><th colspan='2'>Friend</th><th>Last Seen</th><th>Commands</th></tr>";
			$iter = 0;
			foreach( $friendsList as $friendEntry )
			{
				if( $iter++%2==0 )
					echo "<tr>";
				else
					echo "<tr class='alt'>";
					
				$nextFriendName = $friendEntry['Friend'];
				$nextFriendPoints = $friendEntry['RAPoints'];
				$nextFriendActivity = $friendEntry['LastSeen'];
				
				echo "<td>";
				echo "<a href='/User/$nextFriendName'><img src='/UserPic/$nextFriendName.png' height='64' width='64'></a>";
				echo "</td>";
				
				echo "<td>";
				echo "<a href='/User/$nextFriendName'>$nextFriendName ($nextFriendPoints)</a>";
				echo "</td>";
				
				echo "<td>";
				echo "$nextFriendActivity";
				echo "</td>";
				
				echo "<td style='vertical-align:middle;'>";
				echo "<div class='buttoncollection'>";
				echo "<span style='display:block;'><a href='/createmessage.php?t=$user'>Send&nbsp;Message</a></span>";
				echo "<span style='display:block;'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$nextFriendName&amp;a=0'>Remove&nbsp;Friend</a></span>";
				echo "<span style='display:block;'><a href='/requestchangefriend.php?u=$user&amp;c=$cookie&amp;f=$nextFriendName&amp;a=-1'>Block&nbsp;User</a></span>";
				echo "</div>";
				echo "</td>";
				
				echo "</tr>";
			}
			echo "</tbody></table>";
		}
		?>
</div>
<div id="rightcontainer"> 
</div>
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

