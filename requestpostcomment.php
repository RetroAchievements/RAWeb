<?php
	require_once('db.inc.php');
	
	error_log( __FILE__ . " called" );
	
	//	Sanitise!
	if( !ValidatePOSTChars( "uact" ) )
	{
		echo "FAILED";
		return;
	}
	
	$user = seekPOST('u');
	
	$articleID = seekPOST('a');
	$articleType = seekPOST('t');
	settype( $articleID, 'integer' );
	settype( $articleType, 'integer' );
	
	$commentPayload = seekPOST('c');
	$commentPayload = preg_replace('/[^(\x20-\x7F)]*/','', $commentPayload );
	
	if( addArticleComment( $user, $articleType, $articleID, $commentPayload ) )
	{
		error_log( __FILE__ . " returning $articleID" );
		echo $articleID;
	}
	else
	{
		echo "FAILED!";
	}
?>