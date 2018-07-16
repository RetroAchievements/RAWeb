<?php require_once __DIR__ . '/../lib/bootstrap.php';
   
	// http://php.net/manual/en/security.database.sql-injection.php
	
	//error_log( "access to requestupdateachievements.php" );
	
	if( ValidatePOSTChars( "uafv" ) )
	{
		$user = seekPOST( 'u' );
		$achID = seekPOST( 'a' );
		$field = seekPOST( 'f' );
		$value = seekPOST( 'v' );
	}
	else if( ValidateGETChars( "uafv" ) )
	{
		$user = seekGET( 'u' );
		$achID = seekGET( 'a' );
		$field = seekGET( 'f' );
		$value = seekGET( 'v' );
	}
	else
	{	
		error_log( "FAILED access to requestupdateachievements.php" );
		echo "FAILED";
		return;
	}
	
	settype( $achID, "integer" );
	settype( $field, "integer" );
	
	error_log( "Warning: $user changing achievement ID $achID, field $field" );
	
	if( $field == 1 )
	{
		settype( $value, "integer" );
		if( updateAchievementDisplayID( $achID, $value ) )
		{
			echo "OK";
		}
		else
		{
			error_log( "requestupdateachievement.php failed?! 1" . var_dump( $_POST ) );
			echo "FAILED!";
		}
	}
	else if( $field == 2 )	//	Embed video
	{
		$value = str_replace( "_http_", "http", $value );
		
		if( updateAchievementEmbedVideo( $achID, $value ) )
		{
			//header( "Location: " . getenv('APP_URL') . "/Achievement/$achID?e=OK" );
			echo "OK";
		}
		else
		{
			error_log( "requestupdateachievement.php failed?! 2" . var_dump( $_POST ) );
			echo "FAILED!";
		}
	}
	else if( $field == 3 )	//	Flags
	{
		if( updateAchievementFlags( $achID, $value ) )
		{
            header( "Location: " . getenv('APP_URL') . "/Achievement/$achID?e=changeok" );

            if( $value == 3 )
                addArticleComment( "Server", 2, $achID, "\"$user\" promoted this achievement to the Core set." );
            else if( $value == 5 )
                addArticleComment( "Server", 2, $achID, "\"$user\" demoted this achievement to Unofficial." );

        }
        else
        {
			error_log( "requestupdateachievement.php failed?! 3" . var_dump( $_POST ) );
			echo "FAILED!";
		}
	}
	else
	{
		error_log( "requestupdateachievement.php failed?!" . var_dump( $_POST ) );
		echo "FAILED!";
	}
?>
