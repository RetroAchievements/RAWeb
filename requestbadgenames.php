<?php
	require_once('db.inc.php');
	
	//$numFound = getAvailableBadgesList( $dataOut );
	
	$latestRAVBAVer = file_get_contents( "./BadgeIter.txt" );
	echo "OK:$latestRAVBAVer";
	// echo "OK:";
	
	// for( $i = 0; $i < $numFound; $i++ )
	// {
		// echo $dataOut[$i];
		// echo ",";
	// }
?> 