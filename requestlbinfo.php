<?php 
	require_once('db.inc.php');
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	if( !ValidatePOSTChars( "ioc" ) )
	{
		echo "FAILED";
		return;
	}
	
	$lbID = seekPOST( 'i' );
	$offset = seekPOST( 'o' );
	$count = seekPOST( 'c' );
	settype( $lbID, "integer" );
	settype( $offset, "integer" );
	settype( $count, "integer" );
	
	$friendsOnly = 0;
	
	$lbData = GetLeaderboardData( $lbID, NULL, $count, $offset, $friendsOnly );
	$numEntries = count( $lbData );
	
	echo "OK:$lbID:$numEntries:$offset:$count\n";
	
	for( $i = 0; $i < $numEntries; $i++ )
	{
		$nextEntry = $lbData[$i];
		echo $nextEntry['User'] . ':' . $nextEntry['Score'] . ':' . strtotime( $nextEntry['DateSubmitted'] ) . "\n";
	}
?>
