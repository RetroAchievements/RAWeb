<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$consoleID = seekGET( 'c', NULL );
	settype( $consoleID, 'integer' );
	
	$whereClause = "";
	if( $consoleID > 0 )
		$whereClause = "WHERE gd.ConsoleID = $consoleID ";
	
	$query = "SELECT MD5, GameID
			  FROM GameHashLibrary AS ghl
			  LEFT JOIN GameData AS gd ON gd.ID = ghl.GameID
			  $whereClause
			  ORDER BY GameID ASC";
	
	$dbResult = s_mysql_query( $query );
	if( $dbResult !== FALSE )
	{
		while( $nextData = mysqli_fetch_assoc($dbResult) )
			echo $nextData['MD5'] . ":" . $nextData['GameID'] . "\n";
	}
?>
