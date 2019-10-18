<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( !ValidatePOSTChars( "pu" ) )
	{
		echo "ERROR";
		exit;
	}

	$prefs = seekPOST( 'p' );
	$user = seekPOST( 'u' );
	getcookie( $userIn, $cookie );
	
	if( $user == $userIn && validateUser_cookie( $user, $cookie, 0 ) == FALSE )
	{
		echo "ERROR2";
		exit;
	}
	
	$query = "UPDATE UserAccounts SET websitePrefs='$prefs', Updated=NOW() WHERE User='$user'";
	
	log_sql( $query );
	$dbResult = s_mysql_query( $query );
	if( $dbResult !== FALSE )
	{
		error_log( $query );
		error_log( __FILE__ . " user $user to site prefs: $prefs - associate successful!" );
		echo "OK";
	}
	else
	{
		error_log( __FILE__ );
		error_log( $query );
		echo "ERROR3";
	}
?>
