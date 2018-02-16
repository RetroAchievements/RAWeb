<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	//	Sanitise!
	$user = $_POST["u"];
	$token = $_POST["t"];
	
	echo "OK:";
	return;
			
	if( validateUser_app( $user, $token, $fbUser, 0 ) == TRUE )
	{
		$friendList = GetFriendList( $user );
		$numFriends = count( $friendList );
		if( $numFriends >= 0 )
		{
			echo "OK:";
			for( $i = 0; $i < $numFriends; $i++ )
			{
				echo $friendList[$i]["Friend"] . "&";
				echo $friendList[$i]["RAPoints"] . "&";
				echo $friendList[$i]["LastSeen"] . "\n";
			}
		}
		else
		{
			//	error code response
			echo "FAILED!!";
		}
	}
	else
	{
		echo "FAILED!";
	}
	
?>
