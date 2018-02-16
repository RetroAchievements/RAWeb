<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$consoleID = seekPOST( 'c', NULL );
	
	if( !isset( $consoleID ) )
		$consoleID = seekGET( 'c', NULL );
			  
	$whereClause = "";
	if( isset( $consoleID ) )
		$whereClause = "WHERE gd.ConsoleID = $consoleID ";
			  
	$query = "SELECT ID, Title, AchCounts.NumAch
			  FROM GameData AS gd
			  LEFT JOIN ( SELECT COUNT(ID) AS NumAch, GameID FROM Achievements AS ach GROUP BY ach.GameID ) AchCounts ON AchCounts.GameID = gd.ID
			  $whereClause
			  ORDER BY ID ASC";
	
	$dbResult = s_mysql_query( $query );
	if( $dbResult !== FALSE )
	{
		while( $nextData = mysqli_fetch_assoc($dbResult) )
		{
			echo $nextData['ID'] . ":" . $nextData['Title'] . "\n";
		}
	}
?>
