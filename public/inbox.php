<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$maxCount = 10;
	
	$errorCode = seekGET( 'e' );
	$offset = seekGET( 'o', 0 );
	$count = seekGET( 'c', $maxCount );
	$unreadOnly = seekGET( 'u', 0 );
	
	if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
	{
		//	Trying to visit someone's inbox while not being logged in :S
		header( "Location: http://" . AT_HOST . "?e=notloggedin" );
		exit;
	}
	$unreadMessageCount = GetMessageCount( $user, $totalMessageCount );
	
	$pageTitle = $user . "'s inbox";
	$allMessages = GetAllMessages( $user, $offset, $count, $unreadOnly );

	getCookie( $user, $cookieRaw );
	
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
</head>

<body>
<script>
	function MarkAsRead( msgID )
	{
		$("body").find( '#msgInline' + msgID ).toggle(300);
		
		//	If was unread
		var unread = $( '#msgInlineTitle' + msgID + ' span.unreadmsgtitle' );
		if( unread.contents().exists() )
		{
			var posting = $.post( "/requestsetmessageread.php", { u: '<?php echo $user; ?>', m: msgID, r: 0 } );
			posting.done( onMarkAsRead );
		}
	}
	
	function onMarkAsRead( data )
	{
		if( data.substr( 0, 3 ) == 'OK:' )
		{
			var msgID = data.substr( 3 );
			var titleID = "#msgInlineTitle" + msgID;
			if( $("body").find( titleID ).find('span').contents().exists() )
			{
				$("body").find( titleID ).find('span').contents().unwrap();
				
				//	Reduce the number of unread messages by 1
				var numUnread = parseInt( $("body").find( "#messagecounttext" ).find('b').html() );
				numUnread = numUnread - 1;
				$("body").find( "#messagecounttext" ).find('b').html( numUnread );
				
				UpdateMailboxCount( numUnread );
				
				if( numUnread == 0 )
				{
					if( $('#messagecountcontainer').find('big').contents().exists() )
						$('#messagecountcontainer').find('big').contents().unwrap();
				}
			}
		}
	}
	
	function MarkAsUnread( msgID )
	{
		var posting = $.post( "/requestsetmessageread.php", { u: '<?php echo $user; ?>', m: msgID, r: 1 } );
		posting.done( onMarkAsUnread );
	}
	
	function onMarkAsUnread( data )
	{
		if( data.substr( 0, 3 ) == 'OK:' )
		{
			var msgID = data.substr( 3 );
			$( '#msgInline' + msgID ).toggle(300);
			var titleID = "#msgInlineTitle" + msgID;
			
			if( $("body").find( titleID ).find('span').contents().exists() == false )
			{
				$("body").find( titleID ).contents().wrap( "<span class='unreadmsgtitle'>" );
		
				//	Increase the number of unread messages by 1
				var numUnread = parseInt( $("body").find( "#messagecounttext" ).find('b').html() );
				numUnread = numUnread + 1;
				$("body").find( "#messagecounttext" ).find('b').html( numUnread );
				
				if( numUnread > 0 )
				{
					if( $('#messagecountcontainer').find('big').contents().exists() == false )
						$('#messagecountcontainer').contents().wrap('<big>');
				}
				
				UpdateMailboxCount( numUnread );
			}
		}
	}
	
</script>

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
	<div id='leftcontainer'>
	<?php
	//	Left
	RenderErrorCodeWarning( 'left', $errorCode );
	?>
	
	<div id="globalfeed" class="left">
		<?php
			echo "<div class='navpath'>";
			echo "<b>Inbox</b>";
			echo "</div>";
			
			echo "<h2>$pageTitle</h2>";
			
			echo "<div id='messagecounttext'>";
			
			echo "<span id='messagecountcontainer'>";
			echo "<big>You have <b>$unreadMessageCount</b> unread messages</big>";
			echo "</span>";
			
			echo " and $totalMessageCount total messages.";
			
			echo "</div>";
			
			echo "<span class='rightalign clickablebutton'><a href='/createmessage.php'>Create New Message</a></span>";
			if( $unreadOnly )
				echo "<span class='rightalign clickablebutton'><a href='/inbox.php?u=0'>View All Messages</a></span>";
			else
				echo "<span class='rightalign clickablebutton'><a href='/inbox.php?u=1'>View Unread Only</a></span>";
			
			echo "<table class='messagestable' id='messages'><tbody>";
			echo "<tr>";
			echo "<th>Date</th>";
			echo "<th colspan='2'>From</th>";
			echo "<th>Title</th>";
			echo "</tr>";
			
			$totalMsgs = count( $allMessages );
			
			for( $i = 0; $i < $totalMsgs; $i++ )
			{
				$msgID    		= $allMessages[$i]['ID'];
				$msgTime    	= $allMessages[$i]['TimeSent'];
				$msgSentAtNice 	= date( "d/m/y, H:i ", strtotime( $msgTime ) );
				//$msgTo      	= $allMessages[$i]['UserTo'];
				$msgFrom    	= $allMessages[$i]['UserFrom'];
				$msgTitle   	= $allMessages[$i]['Title'];
				$msgPayload 	= $allMessages[$i]['Payload'];
				$msgType    	= $allMessages[$i]['Type'];
				$msgUnread  	= ( $allMessages[$i]['Unread'] == 1 );
				
				$msgPayload = nl2br( $msgPayload );
				$msgPayload = stripslashes( $msgPayload );
				$msgPayload = parseTopicCommentPHPBB( $msgPayload );
				//$msgPayload = str_replace( '\r\n', '<br/>', $msgPayload );
				//$msgPayload = str_replace( '\n', '<br/>', $msgPayload );
				
				$styleAlt = $i%2==1 ? "alt" : "";
				
				echo "<tr class='$styleAlt'>";
				
				echo "<td style='width:15%'>";
				echo "<span id='msgInlineTitleDate$msgID' title='$msgTime'>$msgSentAtNice</span>";
				echo "</td>";
				
				echo "<td style='width:34px'>";
				echo GetUserAndTooltipDiv( $msgFrom, NULL, NULL, NULL, NULL, TRUE );
				echo "</td>";
				echo "<td>";
				echo GetUserAndTooltipDiv( $msgFrom, NULL, NULL, NULL, NULL, FALSE );
				echo "</td>";
				
				//echo "<td>" . $msgTo . "</td>";
				
				echo "<td class='pointer' id='msgInlineTitle$msgID' onclick=\"MarkAsRead( $msgID ); return false;\">";
				echo "<span>";
				if( $msgUnread )
					echo "<span class='unreadmsgtitle'>$msgTitle</span>";
				else
					echo "$msgTitle";
				echo "</span>";
				echo "</td>";
				
				echo "</tr>";
				
				echo "<tr id='msgInline$msgID' class='$styleAlt msgPayload'>";
				echo "<td colspan='4'>";
				echo "<div class='topiccommenttext'>$msgPayload</div>";
				
				echo "<div class='buttoncollection rightfloat'>";
				echo "<span class='rightalign clickablebutton'><a href='#' onclick=\"MarkAsUnread( $msgID ); return false;\" >Mark as unread</a></span>";
				echo "<span class='rightalign clickablebutton'><a href='/createmessage.php?t=$msgFrom&amp;i=$msgID'>Reply</a></span>";
				echo "<span class='rightalign clickablebutton'><a href='/requestdeletemessage.php?u=$user&amp;c=$cookieRaw&amp;m=$msgID'>Delete</a></span>";
				echo "</div>";
				
				echo "</td>";
				echo "</tr>";
			}
			
			echo "</tbody></table>";
			
			echo "<div class='rightalign'>";
			
			if( $offset > 0 )
			{
				echo "<span class='clickablebutton'>";
				echo "<a href='/inbox.php?o=" . ($offset - $maxCount) . "&amp;u=$unreadOnly'>";
				echo "&lt; Previous $maxCount";
				echo "</a>";
				echo "</span>";
			}
			
			if( $totalMsgs == $maxCount )
			{
				echo "<span class='clickablebutton'>";
				echo "<a href='/inbox.php?o=" . ($offset + $maxCount) . "&amp;u=$unreadOnly'>";
				echo "Next $maxCount &gt;";
				echo "</a>";
				echo "</span> ";
			}
			
			echo "</div>";
			
			echo "<br/>";
			
		?>
	</div>
	
	<div id='rightcontainer'>
	<?php
	//	Right
	RenderScoreLeaderboardComponent( $user, $points, TRUE );
	?>
	</div>
</div>

<?php RenderFooter(); ?>

</body>
</html>

