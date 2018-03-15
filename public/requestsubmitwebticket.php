<?php require_once __DIR__ . '/../lib/bootstrap.php';

	if( !ValidatePOSTChars( "ucip" ) )
	{
		echo "FAILED";
		return;
	}

	$user          = seekPOST( 'u' );
	$cookie        = seekPOST( 'c' );
	$achievementID = seekPOST( 'i' );
	$problemType   = seekPOST( 'p' );

	if( isset( $_POST['note'] ) )
	{
		$appendNote = $_POST['note']['description'];

		if( "" != trim( $_POST['note']['checksum'] ))
			$appendNote .= "<br/>MD5: " . $_POST['note']['checksum'];

		if( "" != trim( $_POST['note']['emulator'] ))
		{
			$appendNote .= "<br/>Emulator: " . $_POST['note']['emulator'];

			if( $_POST['note']['emulator'] == "RetroArch" )
				$appendNote .= " (" . $_POST['note']['core'] . ")";
		}

		$note = $appendNote;
	}

	if( validateUser_cookie( $user, $cookie, 0 ) == TRUE )
	{
		$success = submitNewTickets( $user, $achievementID, $problemType, $note, $msgOut );
    if ( $msgOut == "FAILED!")
      header( "Location: http://" . AT_HOST . "/Achievement/$achievementID?e=issue_failed" );
    else
      header( "Location: http://" . AT_HOST . "/Achievement/$achievementID?e=issue_submitted" );

		echo $msgOut;
	}
	else
	{
		echo "FAILED: Cannot validate user! Try logging out and back in, or confirming your email.";
	}
?>
