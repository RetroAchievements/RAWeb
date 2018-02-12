<?php require_once('db.inc.php');
   	
	if( !ValidatePOSTChars( "a" ) )
	{
		echo "FAILED";
		return;
	}
	
	$achID = $_POST["a"];

	//error_log( "test0" );
	if( getAchievementWonData( $achID, $numWinners, $numPossibleWinners, $numRecentWinners, $winnerInfo, NULL ) )
	{
		//error_log( "test4" );
		echo "OK:";
		echo $numWinners;
		echo '*';
		echo $numPossibleWinners;
		echo '*';
		
		foreach( $winnerInfo as $userObj )
		{
			echo $userObj['User'] . '*' . $userObj['DateAwarded'] . '*';
		}
	}
	else
	{
		echo "FAILED: CANNOT FIND ACHIEVEMENT ID!";
	}
?>
