<?php require_once __DIR__ . '/../lib/bootstrap.php';

//	Syntax: request.php?r=addfriend&u=user&t=token&<params>

//	Global anticipated vars:
$requestType 	= seekPOSTorGET( 'r' );
$user 			= seekPOSTorGET( 'u' );
$token 			= seekPOSTorGET( 't' );
$achievementID	= seekPOSTorGET( 'a', 0, 'integer' );
$gameID 		= seekPOSTorGET( 'g', 0, 'integer' );
$bounceReferrer	= seekPOSTorGET( 'b' );

$errorCode = "OK";




function DoRequestError( $errorMsg )
{
	global $user;
	global $requestType;
	error_log( "User: $user, Request: $requestType - $errorMsg" );
}

//	Interrogate requirements:
switch( $requestType )
{
	default:
		DoRequestError( "Unknown Request!" );
		break;

	//	Global, no permissions required:
	
	case "badgeiter":
		$latestRAVBAVer = file_get_contents( "./BadgeIter.txt" );
		echo "OK:$latestRAVBAVer";
		break;
	
	case "codenotes":
		if( !getCodeNotes( $gameID, $codeNotesOut ) )
		{
			DoRequestError( "FAILED!" );
			break;
		}
		echo "OK:$gameID:";
		foreach( $codeNotesOut as $codeNote )
		{
			if( strlen( $codeNote['Note'] ) > 2 )
			{
				//$noteAdj = str_replace( "\n", "\r\n", $codeNote['Note'] );
				echo $codeNote['User'] . ':' . $codeNote['Address'] . ':' . $codeNote['Note'] . "#";
			}
		}
		break;
		
	case "codenotes2":
		if( getCodeNotes( $gameID, $codeNotes ) )
			echo json_encode( $codeNotes );
		break;
		
	case "currentactivity":
		echo json_encode( getLatestRichPresenceUpdates() );
		break;
	
	case "currentlyonline":
		echo json_encode( getCurrentlyOnlinePlayers() );
		break;
			
	case "developerstats":
		echo json_encode( GetDeveloperStats( 99, 0 ) );
		break;
		
	case "score":
		echo getScore( $user );
		break;
		
	case "staticdata":
		echo json_encode( getStaticData() );
		break;
		
	
	//	User-based (require credentials):
		
	case "addfriend":
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
		{
			DoRequestError( "credentials failed!" );
			break;
		}
		
		$newFriend = seekPOSTorGET( 'n' );
		if( addFriend( $user, $newFriend ) )
			echo "OK:Sent Friend Request!";
		else
			DoRequestError( "failed!" );
		
		break;
	
	case "modifyfriend":	//	NEEDS TESTING
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
		{
			DoRequestError( "credentials failed!" );
			break;
		}
		$returnVal = ChangeFriendStatus( $user, seekPOSTorGET( 'f' ), seekPOSTorGET( 'a' ) );
		echo $returnVal;
		
		if( addFriend( $user, $newFriend ) )
			$errorCode = "OK:Sent Friend Request!";
		else
			DoRequestError( "FAILED!" );
			
		break;
	
	case "patch":
		//	Sanitise!
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
		{
			DoRequestError( "credentials failed!" );
			break;
		}
		//if( !ValidatePOSTChars( "ugf" ) )
		//{
		//	DoRequestError( "FAILED!" );
		//	break;
		//}
		
		$errorCode = getPatch( $gameID, seekPOSTorGET( 'f', 0 ), $user, seekPOSTorGET( 'l', 0 ) );
		
		break;
			
	case "createnewlb":
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer ) )
		{
			DoRequestError( "credentials failed!" );
			exit;
		}
		
		if( submitNewLeaderboard( $gameID, $lbID ) )
			$errorCode = "OK:$lbID";
		else
			$errorCode = "FAILED!";
			
		break;
	
	case "recalctrueratio":
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer ) )
		{
			DoRequestError( "credentials failed!" );
			exit;
		}
		
		RecalculateTrueRatio( $gameID );
		
		break;
		
	case "removelbentry":
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Developer ) )
		{
			DoRequestError( "credentials failed!" );
			break;
		}
		$lbID = seekPOSTorGET( 'l', 0, 'integer' );
		$targetUser = seekPOSTorGET( 't' );
		
		error_log( "$user is removing LB entry from $targetUser on LB ID $lbID" );
		
		removeLeaderboardEntry( $targetUser, $lbID );
		break;

	case "removecomment":
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
		{
			DoRequestError( "credentials failed!" );
			break;
		}

		$commentID = seekPOSTorGET( 'c', 0, 'integer' );
		$articleID = seekPOSTorGET( 'a', 0, 'integer' );

		error_log( "$user is removing comment $commentID, type $articleID" );

		RemoveComment( $articleID, $commentID );

		break;
	
	case "getfriendlist":
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Registered ) )
		{
			DoRequestError( "credentials failed!" );
			exit;
		}
		
		$friendList = GetFriendList( $user );
		echo json_encode( $friendList );
		break;
		
	case "uploaduserpic":
		if( !RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Registered ) )
		{
			DoRequestError( "credentials failed!" );
			exit;
		}
		
		$filename = seekPOSTorGET( 'f' );
		$rawImage = seekPOSTorGET( 'i' );
		$response = UploadUserPic( $user, $filename, $rawImage );
		echo $response['Success'] ? "OK" : $response['Error'];
		//echo json_encode( $response );
		
		break;
}

//	Universal error handling (typically 'OK')
if( $bounceReferrer )
{
	$errorCode = ( strpos($_SERVER['HTTP_REFERER'], "?") !== FALSE ) ? "&e=$errorCode" : "?e=$errorCode";
	header( "Location: " . $_SERVER['HTTP_REFERER'] . $errorCode );
}
else
{
	if( $errorCode !== 'OK' )
		echo "$errorCode";
}
	
exit;

?>
