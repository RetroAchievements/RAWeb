<?php require_once('db.inc.php');
   
	$user = $_POST["User"];
	$pass = $_POST["Pass"];
	//$type = $_POST["Type"];
	
	if( validateUser( $user, $pass, $fbUser, 0 ) == TRUE )
	{
		$numIDs = getUnreadMessageIDs( $user, $aIDs );
		
		for( $i = 0; $i < $numIDs; $i++ )
		{
			$nextID = $aIDs[$i];
			echo $nextID;
		}
	}
	else
	{
		echo "FAILED:INVALID USER/PASS!";
	}
?>
