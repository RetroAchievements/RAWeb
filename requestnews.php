<?php require_once('db.inc.php');
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	$numArticles = seekPost( 'n', 3 );
	settype( $numArticles, "integer" );
	$fromApp = seekPost( 'a', 0 );
	settype( $fromApp, "integer" );
	
	if( !isset( $fromApp ) || $fromApp == "" )
		$fromApp = 0;
	
	$newsCount = getLatestNewsHeaders( 0, $numArticles, $newsData );
	
	for( $i = 0; $i < $newsCount; $i++ )
	{
		$nextNews = $newsData[$i];
		
		echo $nextNews['TimePosted'] . "\n";		
		echo $nextNews['Title'] . "\n";

		if( $fromApp )
			echo strip_tags( str_replace( "\n", "", $nextNews['Payload'] ) );
		else
			echo $nextNews['Payload'];

		echo "\n";
	}
?>
