<?php
require_once __DIR__ . '/../lib/bootstrap.php';

error_log( "RequestPatreonUpdate Start 6" );
error_log( print_r( getallheaders(), true ) );
error_log( print_r( $_SERVER, true ) );     //
error_log( print_r( $_REQUEST, true ) );  //a, empty
error_log( print_r( $_POST, true ) );     // empty
error_log( print_r( $_GET, true ) );      //a, empty
error_log( print_r( $HTTP_RAW_POST_DATA, true ) );

$rawdata = file_get_contents( 'php://input' );
error_log( "Raw data: [" . $rawdata . "]" );

error_log( "RequestPatreonUpdate End 6" );

//$patreonEvent = $_SERVER[ 'HTTP_X_PATREON_EVENT' ];
//$patreonSignature = $_SERVER[ 'HTTP_X_PATREON_SIGNATURE' ];


/*$patreonSignUnhashed = hash_hmac( 'md5', $patreonSignature, getenv('PATREON_CLIENT_SECRET'), false );
error_log( $patreonSignUnhashed );

$patreonSignUnhashed = hash_hmac( 'md5', $patreonSignature, getenv('PATREON_CLIENT_SECRET'), true );
error_log( $patreonSignUnhashed );
function verify_webhook( $patreonSignature, $hmac_header )
{
    error_log( hash_hmac( 'sha256', $patreonSignature, getenv('PATREON_CLIENT_SECRET'), true ) );
    error_log( json_decode( hash_hmac( 'sha256', $patreonSignature, getenv('PATREON_CLIENT_SECRET'), true ) ) );
    $calculated_hmac = base64_encode( hash_hmac( 'sha256', $patreonSignature, getenv('PATREON_CLIENT_SECRET'), true ) );
    error_log( $calculated_hmac );
    error_log( json_decode( $calculated_hmac ) );
    return ($hmac_header == $calculated_hmac);
}

//$hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
//error_log( $data );
$verified = verify_webhook( $patreonSignature, $patreonSignature );
error_log( 'Webhook verified: ' . var_export( $verified, true ) ); //check error.log to see the result
 *
 */
