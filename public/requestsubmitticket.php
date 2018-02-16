<?php require_once __DIR__ . '/../lib/bootstrap.php';
   	
	if( !ValidatePOSTChars( "utipn" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user 		 = seekPOST( 'u' );
	$token 		 = seekPOST( 't' );
	$idListCSV 	 = seekPOST( 'i' );
	$problemType = seekPOST( 'p' );
	$note 		 = seekPOST( 'n' );
	
	if( validateUser_app( $user, $token, $unused, 1 ) == TRUE )
	{
		$success = submitNewTickets( $user, $idListCSV, $problemType, $note, $msgOut );
		echo $msgOut;
	}
	else
	{
		echo "FAILED: Cannot validate user! Try logging out and back in, or confirming your email.";
	}
?>
