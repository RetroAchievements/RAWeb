<?php 
	require_once __DIR__ . '/../lib/bootstrap.php';
	
	if( !ValidatePOSTChars( "uct" ) )
	{
		header( "Location: " . getenv('APP_URL') . "?e=invalidparams" );
		exit;
	}

    global $db;
        
	$user = seekPOST( 'u' );
	$cookie = seekPOST( 'c' );
    $prefType = seekPOST( 't' );
    $value = seekPOST( 'v', 0 );
    settype( $value, 'integer' );
    error_log("value=$value");
	
	if( validateUser_cookie( $user, $cookie, 1 ) )
	{
        if( $prefType == 'wall' )
        {
            $query = "UPDATE UserAccounts
                SET UserWallActive=$value
                WHERE User='$user'";

            $dbResult = mysqli_query( $db, $query );
            if( $dbResult !== FALSE )
            {
                error_log( $query );
                error_log( __FILE__ . " user $user to $prefType=$value - successful!" );
                $changeErrorCode = "changeok";
            }
            else
            {
                error_log( __FILE__ );
                error_log( $query );
                $changeErrorCode = "changeerror";
            }
        }
        else if( $prefType == 'cleanwall' )
        {
            $query = "DELETE FROM Comment
                      WHERE ArticleType = 3 && ArticleID = ( SELECT ua.ID FROM UserAccounts AS ua WHERE ua.User = '$user' )";
            
            $dbResult = mysqli_query( $db, $query );
            if( $dbResult !== FALSE )
            {
                error_log( $query );
                error_log( __FILE__ . " user $user to $prefType=$value - successful!" );
                $changeErrorCode = "changeok";
            }
            else
            {
                error_log( __FILE__ );
                error_log( $query );
                $changeErrorCode = "changeerror";
            }
        }
	}
	else
	{
		error_log( __FILE__ );
		error_log( $query );
		$changeErrorCode = "changeerror";
	}
	
	header( "Location: " . getenv('APP_URL') . "/controlpanel.php?e=$changeErrorCode" );
?>
