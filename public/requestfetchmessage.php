<?php require_once __DIR__ . '/../lib/bootstrap.php';
   
	$user = $_POST["User"];
	$pass = $_POST["Pass"];
	
	$idRequested = $_POST["ID"];
	
	if( validateUser( $user, $pass, $fbUser, 0 ) == TRUE )
	{
		if( getMessageByID( $user, $idRequested, $data ) )
		{
			echo $data['MessageTitle'];
			echo ":";
			echo $data['MessagePayload'];
		}
	}
	else
	{
		echo "FAILED:INVALID USER/PASS!";
	}
?>

