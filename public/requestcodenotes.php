<?php
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( !ValidatePOSTorGETChars( "g" ) )
	{
		echo "FAILED";
		return;
	}
	
	$gameID = seekPOSTorGET( 'g', 0, 'integer' );
	echo ParseCURLPage( "request.php", "r=codenotes&g=$gameID" );
?>
