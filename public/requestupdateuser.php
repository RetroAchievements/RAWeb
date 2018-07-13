<?php
require_once __DIR__ . '/../lib/bootstrap.php';

// http://php.net/manual/en/security.database.sql-injection.php

if( ValidatePOSTorGETChars( "tpv" ) )
{
    $targetUser = seekPOSTorGET( 't' );
    $propertyType = seekPOSTorGET( 'p' );
    $value = seekPOSTorGET( 'v' );
}
else
{
    echo "FAILED";
    return;
}

settype( $propertyType, 'integer' );
settype( $value, 'integer' );

if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer ) )
{
    if( $propertyType == 0 ) //	account permissions
    {
        $response = SetAccountPermissionsJSON( $user, $permissions, $targetUser, $value );

        if( $response[ 'Success' ] )
        {
            error_log( "$user updated $targetUser to $value OK!!" );
            header( "Location: " . getenv('APP_URL') . "/User/$targetUser?e=OK" );
        }
        else
        {
            log_email( "requestupdateuser failed: " . $response[ 'Error' ] );
            error_log( "requestupdateuser.php failed?! 0" . $response[ 'Error' ] );
            echo "Failed: " . $response[ 'Error' ];
        }
    }
    else if( $propertyType == 1 ) //	forum post permissions
    {
        if( setAccountForumPostAuth( $user, $permissions, $targetUser, $value ) )
        {
            error_log( "$user updated $targetUser to $value OK!!" );
            header( "Location: " . getenv('APP_URL') . "/User/$targetUser?e=OK" );
        }
        else
        {
            log_email( "requestupdateuser.php failed?! 1" );
            error_log( "requestupdateuser.php failed?! 1" );
            echo "FAILED!";
        }
    }
    else if( $propertyType == 2 )   //  Toggle Patreon badge
    {
        $hasBadge = HasPatreonBadge( $targetUser );
        SetPatreonSupporter( $targetUser, !$hasBadge );

        $hasBadge = !$hasBadge;
        error_log( "$user updated $targetUser to Patreon Status $hasBadge OK!!" );
        header( "Location: " . getenv('APP_URL') . "/User/$targetUser?e=OK" );
    }
    else if( $propertyType == 3 )   //  Toggle 'Untracked' status
    {
        SetUserTrackedStatus( $targetUser, $value );
        error_log( "SetUserTrackedStatus, $targetUser => $value" );
        header( "Location: http://" . AT_HOST . "/User/$targetUser?e=OK" );
    }
}
else
{
    log_email( "requestupdateuser.php failed?! 2" );
    error_log( "requestupdateuser.php failed?! 2" );
    echo "FAILED!";
}
