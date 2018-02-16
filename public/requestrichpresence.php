<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	//	Sanitise!
	if( !ValidatePOSTChars( "g" ) )
	{
		echo "FAILED";
		return;
	}
	
	$gameID = seekPOST( 'g' );
	settype( $gameID, 'integer' );
	
	if( GetRichPresencePatch( $gameID, $dataOut ) )
	{
		echo $dataOut;
		//echo "OK";
	}
	else
	{
		// nothing.
		//echo "FAILED!";
	}
	
?>
