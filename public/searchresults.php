<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';

	RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	
	$searchQuery = seekGET( 's', NULL );
	$offset = seekGET('o', 0);
	$maxCount = seekGET('c', 50);
	
	$resultsCount = 0;
	if( $searchQuery !== NULL )
		$resultsCount = performSearch( $searchQuery, $offset, $maxCount, $searchResults );
	
	$pageTitle = "Search";
	$errorCode = seekGET('e');
	RenderDocType();
?>

<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
</head>

<body>
<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id="mainpage">
	<div id="fullcontainer">
		
		<?php
		echo "<div class='navpath'>";
		echo "<b>Search</b></a>";
		echo "</div>";
		
		echo "<h2 class='longheader'>Search</h2>";

		echo "<div class='searchbox longer'>";
		echo "<form action='/searchresults.php' method='get'>";
		//echo "Search:&nbsp;";
		echo "<input size='42' name='s' type='text' class='searchboxinput' value='$searchQuery' />";
		echo "&nbsp;&nbsp;";
		echo "<input type='submit' value='Search' />";
		echo "</form>";
		echo "</div>";
		
		if( $searchQuery !== NULL )
		{
			echo "<h4 class='longheader'>Results:</h4>";
			if( $resultsCount == 0 )
			{
				echo "No results found!";
			}
			else
			{
				echo "<table class='smalltable'><tbody>";
				echo "<tr>";
				echo "<th>Type</th>";
				echo "<th colspan='2'>Match</th>";
				echo "</tr>";
				$lastType = '';
				$iter = 0;
				foreach( $searchResults as $nextResult )
				{
					$nextType = $nextResult['Type'];
					$nextID = $nextResult['ID'];
					$nextTarget = $nextResult['Target'];
					$nextTitle = strip_tags( $nextResult['Title'] );
					
					if( $nextType !== $lastType )
					{
						$lastType = $nextType;
						//echo "<tr><td colspan=2><b>$nextType</b></td></tr>";
					}
						
					if( $iter++%2 == 0)
						echo "<tr>";
					else
						echo "<tr class='alt'>";
						
					echo "<td>$nextType</td>";
					//echo "<td>$nextID</td>";
					if( $nextType == 'User' )
					{
						echo "<td>";
						echo GetUserAndTooltipDiv( $nextID, TRUE );
						echo "</td>";
						echo "<td>";
						echo GetUserAndTooltipDiv( $nextID, FALSE );
						echo "</td>";
					}
					else if( $nextType == 'Achievement' )
					{
						$achData = GetAchievementData( $nextID );
						$badgeID = $achData['BadgeName'];
						echo "<td>";
						echo "<img src='" . getenv('APP_STATIC_URL') . "/Badge/" . str_pad($badgeID, 5, '0', STR_PAD_LEFT) . ".png' title='$nextTitle' alt='$nextTitle' width='32' height='32' />";
						echo "</td>";
						echo "<td><a href='$nextTarget'>$nextTitle</a></td>";
					}
					else if( $nextType == 'Forum Comment' || $nextType == 'Comment' )
					{
						echo "<td>";
						echo GetUserAndTooltipDiv( $nextID, TRUE );
						echo "</td>";
						echo "<td><a href='$nextTarget'>$nextTitle</a></td>";
					}
					else
					{
						echo "<td colspan=2><a href='$nextTarget'>$nextTitle</a></td>";
					}
					
					echo "</tr>";
				}
				
				echo "</tbody></table>";
						
				echo "<div class='rightalign row'>";
				if( $offset > 0 )
				{
					$prevOffset = $offset - $maxCount;
					echo "<a href='/searchresults.php?s=$searchQuery&amp;o=$prevOffset'>&lt; Previous $maxCount</a> - ";
				}
				if( $resultsCount == $maxCount )
				{
					//	Max number fetched, i.e. there are more. Can goto next 25.
					$nextOffset = $offset + $maxCount;
					echo "<a href='/searchresults.php?s=$searchQuery&amp;o=$nextOffset'>Next $maxCount &gt;</a>";
				}
				echo "</div>";
				
			}
			
		}
		?>
		
		<br/>
	</div> 
</div>	
  
<?php RenderFooter(); ?>

</body>
</html>

