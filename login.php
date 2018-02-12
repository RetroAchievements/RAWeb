<?php
//error_log( __FUNCTION__ . "0" );
require_once('db.inc.php');

$user = $_POST[ "u" ];
$pass = $_POST[ "p" ];
$redir = $_POST[ "r" ];
$fbUser = "";
$cookie = "";

if( validateUser( $user, $pass, $fbUser, 0 ) )
{
    generateCookie( $user, $cookie );

    //	TBD: Check for messages, updates? etc
    //	Post activity of login:
    postActivity( $user, ActivityType::Login, null );

    //	Remove 'incorrect password' from redir url:
    $redir = str_replace( "e=incorrectpassword", "", $redir );
    //	Remove 'notloggedin'
    $redir = str_replace( "e=notloggedin", "", $redir );

    header( "Location: http://" . AT_HOST . "$redir" );
}
else
{
    if( isset( $redir ) && stristr( $redir, "?" ) )
    {
        header( "Location: http://" . AT_HOST . "$redir&e=incorrectpassword" ); //	if redir has a query string, append errorcode!
    }
    else
    {
        header( "Location: http://" . AT_HOST . "$redir?e=incorrectpassword" );
    }
}