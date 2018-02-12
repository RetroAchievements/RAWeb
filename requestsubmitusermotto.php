<?php 
	require_once('db.inc.php');
	
	if( !ValidatePOSTChars( "ucm" ) )
	{
		header( "Location: http://" . AT_HOST . "?e=invalidparams" );
		exit;
	}

    global $db;
        
	$user = seekPost( 'u' );
	$cookie = seekPost( 'c' );
	$newMotto = mysqli_real_escape_string( $db, seekPost( 'm' ) );
	
	error_log( "$user changing motto to $newMotto" );
	
	if( validateUser_cookie( $user, $cookie, 1 ) )
	{
		$query = "
			UPDATE UserAccounts
			SET Motto='$newMotto'
			WHERE User='$user'";
			
		$dbResult = mysqli_query( $db, $query );
		if( $dbResult !== FALSE )
		{
			error_log( $query );
			error_log( __FILE__ . " user $user to $newMotto - associate successful!" );
			$changeErrorCode = "changeok";
		}
		else
		{
			error_log( __FILE__ );
			error_log( $query );
			$changeErrorCode = "changeerror";
		}
	}
	else
	{
		error_log( __FILE__ );
		error_log( $query );
		$changeErrorCode = "changeerror";
	}
	
	header( "Location: http://" . AT_HOST . "/controlpanel.php?e=$changeErrorCode" );
?>