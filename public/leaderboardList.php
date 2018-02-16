<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';

	$consoleList = getConsoleList();
	$consoleIDInput = seekGET( 'c', 0 );

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	
	getCookie( $user, $cookie );
	
	$permissions = 0;
	if( isset( $user ) )
		$permissions = getUserPermissions( $user );

	$maxCount = 25;
	
	$count = seekGET( 'c', $maxCount );
	$offset = seekGET( 'o', 0 );
	
	$gameID = seekGET( 'g', NULL );
	
	if( isset( $gameID ) )
		$sortBy = seekGET( 's', 0 );	//	If a game is picked, sort the LBs by DisplayOrder
	else
		$sortBy = seekGET( 's', 3 );
		
	$lbCount = getLeaderboardsList( $consoleIDInput, $gameID, $sortBy, $count, $offset, $lbData );
	
	unset( $gameData );
	if( $gameID != 0 )
	{
		$gameData = getGameData( $gameID );
		getCodeNotes( $gameID, $codeNotes );
	}
	
	//var_dump( $lbData );
	//var_dump( $gameData );
	
	$requestedConsole = "";
	if( $consoleIDInput !== 0 )
		$requestedConsole = " " . $consoleList[$consoleIDInput];
	
	$pageTitle = "Leaderboard List" . $requestedConsole;

	$errorCode = seekGET('e');
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
</head>

<body>
<script>

function ReloadLBPage()
{
	var gameID = $( '#gameselector' ).val();
	location.href='/leaderboardList.php?g=' + gameID;
}

</script>

<?php
if( $permissions >= \RA\Permissions::Developer )
{
?>
<script>
function UpdateLeaderboard( user, lbID )
{
	var lbTitle = $.trim( $("body").find( "#LB_"+lbID+"_Title" ).val() );
	var lbDesc = $.trim( $("body").find( "#LB_"+lbID+"_Desc" ).val() );
	var lbFormat = $.trim( $("body").find( "#LB_"+lbID+"_Format" ).val() );
	var lbDisplayOrder = $.trim( $("body").find( "#LB_"+lbID+"_DisplayOrder" ).val() );
	var lbMem1 = $.trim( $("body").find( "#LB_"+lbID+"_Mem1" ).val() );
	var lbMem2 = $.trim( $("body").find( "#LB_"+lbID+"_Mem2" ).val() );
	var lbMem3 = $.trim( $("body").find( "#LB_"+lbID+"_Mem3" ).val() );
	var lbMem4 = $.trim( $("body").find( "#LB_"+lbID+"_Mem4" ).val() );
	
	var lbMem = "STA:"+lbMem1+"::CAN:"+lbMem2+"::SUB:"+lbMem3+"::VAL:"+lbMem4;
	var lbLowerIsBetter = $("body").find( "#LB_"+lbID+"_LowerIsBetter" ).is(':checked') ? '1' : '0';
	
	var posting = $.post( "requestupdatelb.php", { u: user, i: lbID, t: lbTitle, d: lbDesc, f: lbFormat, m: lbMem, l: lbLowerIsBetter, o: lbDisplayOrder } );
	posting.done( onUpdateComplete );
	
	$("body").find( "#warning" ).html( "Status: updating..." );
}

function onUpdateComplete( data )
{
	//alert( data );
	if( data !== "OK" )
	{
		$("body").find( "#warning" ).html( "Status: Errors..." + data );
		//alert( data );
	}
	else
	{
		$("body").find( "#warning" ).html( "Status: OK!" );
	}
}

</script>

<?php
}
?>
<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
<div id='leftcontainer'>
<?php	
	echo "<div class='left'>";
		echo "<div class='navpath'>";
		if( $gameID != 0 )
		{
			echo "<a href='/leaderboardList.php'>Leaderboard List</a>";
			echo " &raquo; <b>" . $gameData['Title'] . "</b>";
		}
		else
		{
			echo "<b>Leaderboard List</b>";	//	NB. This will be a stub page
		}
		echo "</div>";
		
		echo "<div class='detaillist'>";
		echo "<h3 class='longheader'>Leaderboard List</h3>";
		
		if( isset( $gameData ) )
		{
			echo "<div>";
			echo "Displaying leaderboards for: ";
			echo GetGameAndTooltipDiv( $gameData['ID'], $gameData['Title'], $gameData['ImageIcon'], $gameData['ConsoleName'] );
			echo "</div>";
			
			echo "<div>";
			echo "<a href='/leaderboardList.php'>Return to full list</a>";
			echo "</div>";
		}
		
		if( isset( $user ) && $permissions >= 3 )
		{
			$numGames = getGamesList( 0, $gamesList );
			
			echo "<div class='devbox'>";
			echo "<span onclick=\"$('#devboxcontent').toggle(500); return false;\">Dev (Click to show):</span><br/>";
			echo "<div id='devboxcontent'>";
			
			echo "<ul>";
			if( isset( $gameID ) )
			{
				echo "<li>";
				echo "$user<a href='requestcreatenewlb.php?u=EXTERNAL_FRAGMENT&amp;c=EXTERNAL_FRAGMENT&amp;g=EXTERNAL_FRAGMENT'>Add New Leaderboard to $cookie$gameID" . $gameData['Title'] . "</a>";
				echo "</li>";
			}
			else
			{
				echo "<li>Add new leaderboard</br>";
				echo "<form method='post' action='requestcreatenewlb.php' >";
				echo "<input type='hidden' name='u' value='$user' />";
				echo "<input type='hidden' name='c' value='$cookie' />";
				echo "<select name='g'>";
				foreach( $gamesList as $nextGame )
				{
					$nextGameID = $nextGame['ID'];
					$nextGameTitle = $nextGame['Title'];
					$nextGameConsole = $nextGame['ConsoleName'];
					echo "<option value='$nextGameID'>$nextGameTitle ($nextGameConsole)</option>";
				}
				echo "</select>";
				echo "&nbsp;<input type='submit' value='Create New Leaderboard' /></br></br>";
				echo "</form>";
				echo "</li>";
			}
			echo "</ul>";
			
			echo "</div>";
			echo "</div>";
		}
		
		if( isset( $gameData ) && isset( $user ) && $permissions >= 3 )
			echo "<div id='warning'>Status: OK!</div>";
		
		if( !isset( $gameData ) )
		{
			$uniqueGameList = Array();
			foreach( $lbData as $nextLB )
			{
				if( !isset( $uniqueGameList[ $nextLB['GameID'] ] ) )
				{
					$uniqueGameList[ $nextLB['GameID'] ] = $nextLB;
					$uniqueGameList[ $nextLB['GameID'] ]['NumLeaderboards'] = 1;
				}
				else
				{
					$uniqueGameList[ $nextLB['GameID'] ]['NumLeaderboards']++;
				}
			}
			
			echo "Pick a game:";
			echo "<select id='gameselector' onchange=\"ReloadLBPage();\">";
			echo "<option>--</option>";
			$lastConsoleName = '';
			foreach( $uniqueGameList as $gameID => $nextEntry )
			{
				if( $nextEntry['ConsoleName'] !== $lastConsoleName )
				{
					$lastConsoleName = $nextEntry['ConsoleName'];
					echo "<option>-= $lastConsoleName =-</option>";
				}
					
				echo "<option value='$gameID'>" . $nextEntry['GameTitle'] . " (" . $nextEntry['ConsoleName'] . ") (" . $nextEntry['NumLeaderboards'] . " LBs) " . "</option>";
			}
			echo "</select>";
		}
		
		
		
		echo "<table class='smalltable xsmall'><tbody>";
		
		$sort1 = ($sortBy==1) ? 11 : 1;
		$sort2 = ($sortBy==2) ? 12 : 2;
		$sort3 = ($sortBy==3) ? 13 : 3;
		$sort4 = ($sortBy==4) ? 14 : 4;
		$sort5 = ($sortBy==5) ? 15 : 5;
		$sort6 = ($sortBy==6) ? 16 : 6;
		$sort7 = ($sortBy==7) ? 17 : 7;
		
		if( isset( $gameData ) && isset( $user ) && $permissions >= 3 )
		{
			echo "<th>ID</th>";
			echo "<th>Title/Description</th>";
			echo "<th>Type</th>";
			echo "<th>Lower Is Better</th>";
			echo "<th>Display Order</th>";
		}
		else
		{
			echo "<th><a href='/leaderboardList.php?s=EXTERNAL_FRAGMENT'>ID<$sort1/a></th>";
			echo "<th><a href='/leaderboardList.php?s=EXTERNAL_FRAGMENT'>Gam$sort2e</a></th>";
			echo "<th><a href='/leaderboardList.php?s=EXTERNAL_FRAGMENT'>Con$sort3sole</a></th>";
			echo "<th><a href='/leaderboardList.php?s=EXTERNAL_FRAGMENT'>Tit$sort4le</a></th>";
			echo "<th><a href='/leaderboardList.php?s=EXTERNAL_FRAGMENT'>Des$sort5cription</a></th>";
			echo "<th><a href='/leaderboardList.php?s=EXTERNAL_FRAGMENT'>Typ$sort6e</a></th>";
			echo "<th><a href='/leaderboardList.php?s=EXTERNAL_FRAGMENT'>Num$sort7Results</a></th>";
		}
		
		$listCount = 0;
		
		foreach( $lbData as $nextLB )
		{
			$lbID = $nextLB['ID'];
			$lbTitle = $nextLB['Title'];
			$lbDesc = $nextLB['Description'];
			$lbMem = $nextLB['Mem'];
			$lbFormat = $nextLB['Format'];
			$lbLowerIsBetter = $nextLB['LowerIsBetter'];
			$lbNumEntries = $nextLB['NumResults'];
			settype( $lbNumEntries, 'integer' );
			$lbDisplayOrder = $nextLB['DisplayOrder'];
			$gameID = $nextLB['GameID'];
			$gameTitle = $nextLB['GameTitle'];
			$gameIcon = $nextLB['GameIcon'];
			$consoleName = $nextLB['ConsoleName'];
			
			$niceFormat = ( $lbLowerIsBetter ? "Smallest " : "Largest " ) . ( ( $lbFormat == "SCORE" ) ? "Score" : "Time" );
			
			if( $listCount++%2 == 0 )
				echo "<tr>";
			else
				echo "<tr class='alt'>";
			
			if( isset( $gameData ) && isset( $user ) && $permissions >= 3 )
			{
				echo "<td>";
				echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbID</a>";
				echo "</td>";
				
				//echo "<td>";
				//echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName );
				//echo "</td>";
				
				// echo "<td>";
				// echo "$consoleName";
				// echo "</td>";
				
				echo "<td>";
				echo "<input style='width: 60%;' type='text' value='$lbTitle' id='LB_" . $lbID . "_Title' /></br>";
				echo "<input style='width: 100%;' type='text' value='$lbDesc' id='LB_" . $lbID . "_Desc' />";
				echo "</td>";
				
				
				echo "<td style='width: 20%;'>";
				echo "<select id='LB_" . $lbID . "_Format' name='i' >";
				$selected = $lbFormat=="SCORE" ? "selected" : "";
				echo "<option value='SCORE' $selected>Score</option>";
				$selected = $lbFormat=="TIME" ? "selected" : "";
				echo "<option value='TIME' $selected >Time (Frames)</option>";
				$selected = $lbFormat=="MILLISECS" ? "selected" : "";
				echo "<option value='MILLISECS' $selected >Time (Milliseconds)</option>";
				$selected = $lbFormat=="VALUE" ? "selected" : "";
				echo "<option value='VALUE' $selected>Value</option>";
				echo "</select>";
				
				//echo "<input type='text' value='$lbFormat' id='LB_" . $lbID . "_Format' />";
				echo "</td>";
				
				echo "<td style='width: 10%;'>";
				$checked = ( $lbLowerIsBetter ? "checked" : "" );
				echo "<input type='checkbox' $checked id='LB_" . $lbID . "_LowerIsBetter' />";
				echo "</td>";
				
				echo "<td style='width: 10%;'>";
				echo "<input size='3' type='text' value='$lbDisplayOrder' id='LB_" . $lbID . "_DisplayOrder' />";
				echo "</td>";
				
				echo "</tr>";
				
				if( $listCount++%2 == 0 )
					echo "<tr>";
				else
					echo "<tr class='alt'>";
					
				echo "<td>";
				//echo "Memory:";
				echo "</td>";
				echo "<td colspan='4'>";
				$memChunks = explode( "::", $lbMem );
				foreach( $memChunks as &$memChunk )
				{
					$memChunk = substr( $memChunk, 4 );	//	Remove STA: CAN: SUB: and VAL:
				}
				//var_dump( $memChunks );
				//echo "<input typ
				//echo "<textarea id='LB_" . $lbID . "_Mem' style='width:88%' class='fullwidth' onchange=\"UpdateLeaderboard('$user', '$lbID')\" >$lbMem</textarea>";
				
				echo "<table class='smalltable xsmall nopadding' ><tbody>";
				
				echo "<tr>";
				echo "<td style='width:10%;' >Start:</td>";
				echo "<td>";
				echo "<input type='text' id='LB_" . $lbID . "_Mem1' value='$memChunks[0]' style='width: 100%;' />";
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td style='width:10%;'>Cancel:</td>";
				echo "<td>";
				echo "<input type='text' id='LB_" . $lbID . "_Mem2' value='$memChunks[1]' style='width: 100%;' />";
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td style='width:10%;'>Submit:</td>";
				echo "<td>";
				echo "<input type='text' id='LB_" . $lbID . "_Mem3' value='$memChunks[2]' style='width: 100%;' />";
				echo "</td>";
				echo "</tr>";
				
				echo "<tr>";
				echo "<td style='width:10%;'>Value:</td>";
				echo "<td>";
				echo "<input type='text' id='LB_" . $lbID . "_Mem4' value='$memChunks[3]' style='width: 100%;' />";
				echo "</td>";
				echo "</tr>";
				
				echo "</tbody></table>";
				
				echo "<div style='float:left;' >";
				echo "&#124;";
				echo "&nbsp;";
				echo "$user<a href='requestdeletelb.php?u=EXTERNAL_FRAGMENT&amp;i=EXTERNAL_FRAGMENT&g=EXTERNAL_FRAGMENT'>Permanently Delete?</a>$lbID$gameID";
				echo "&nbsp;";
				echo "&#124;";
				echo "&nbsp;";
				
				if( $lbNumEntries > 0 )
					echo "<a href='requestresetlb.php?u=EXTERNAL_FRAGMENT&amp;i=EXTERNAL_FRAGMENT'>Reset all $user$lbID$lbNumEntries entries?</a>";
				else
					echo "0 entries";
					
				echo "&nbsp;";
				echo "&#124;";
				
				echo "</div>";
				echo "<div class='rightalign'><input type='submit' name='Update' onclick=\"UpdateLeaderboard('$user', '$lbID')\" value='Update'></input></div>";
				
				echo "</td>";
				
				echo "</td>";
			}
			else
			{
				echo "<td>";
				echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbID</a>";
				echo "</td>";
				
				echo "<td>";
				echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameIcon, $consoleName, TRUE, 32, FALSE );
				echo "</td>";
				
				echo "<td>";
				echo "$consoleName";
				echo "</td>";
				
				echo "<td>";
				echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a>";
				echo "</td>";
				
				echo "<td>";
				echo "$lbDesc";
				echo "</td>";
				
				echo "<td>";
				echo "$niceFormat";
				echo "</td>";
				
				echo "<td>";
				echo "<a href='/leaderboardinfo.php?i=$lbID'>$lbNumEntries</a>";
				echo "</td>";
			}
				
			echo "</tr>";
		}
		
		//	hack:
		if( isset( $gameData ) && isset( $user ) && $permissions >= 3 )
			$listCount /= 2;
		
		echo "</tbody></table>";
		echo "</div>";
		
		echo "<div class='rightalign row'>";
		if( $offset > 0 )
		{
			$prevOffset = $offset - $maxCount;
			echo "<a href='/achievementList.php?s=$sortBy&amp;o=$prevOffset&amp;p=$params'>&lt; Previous $maxCount</a> - ";
		}
		if( $listCount == $maxCount )
		{
			//	Max number fetched, i.e. there are more. Can goto next 25.
			$nextOffset = $offset + $maxCount;
			echo "<a href='/achievementList.php?s=$sortBy&amp;o=$nextOffset&amp;p=$params'>Next $maxCount &gt;</a>";
		}
		echo "</div>";
		
		?>
		<br/>
	</div> 
</div>

<div id='rightcontainer'>
	<?php /*RenderRecentlyUploadedComponent( 10 );*/ ?>
	
	<?php
	echo "<div class='right'>";
		if( isset( $gameData ) && isset( $user ) && $permissions >= 3 )
		{
			RenderCodeNotes( $codeNotes );
		}
	echo "</div>";
?>
</div>


</div>
  
<?php RenderFooter(); ?>

</body>
</html>

