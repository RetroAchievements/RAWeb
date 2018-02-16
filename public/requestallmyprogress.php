<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	$user = seekGET( 'u', NULL );
	$consoleID = seekGET( 'c', NULL );
	
	$allProgress = GetAllUserProgress( $user, $consoleID );
	foreach( $allProgress as $gameID => $nextData )
		echo $gameID . ":" . $nextData['NumAch'] . ":" . $nextData['Earned'] . ":" . $nextData['HCEarned'] . "\n";
?>
