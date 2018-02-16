<?php
//	Internal: this is not public-facing!
require_once __DIR__ . '/../../lib/bootstrap.php';

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$user = seekGET( 'u', NULL );
$recentGamesPlayed = seekGET( 'g', 5 );
$recentAchievementsEarned = seekGET( 'a', 10 );

$retVal = array();

getUserPageInfo( $user, $retVal, $recentGamesPlayed, $recentAchievementsEarned, NULL );

getAccountDetails( $user, $userDetails );

$retVal['ID'] = $userDetails['ID'];
$retVal['Points'] = $userDetails['RAPoints'];
$retVal['Motto'] = $userDetails['Motto'];
$retVal['UserPic'] = "/UserPic/" . $user . ".png";
$retVal['Rank'] = getUserRank( $user );

//	Find out if we're online or offline
$retVal['LastActivity'] = getActivityMetadata( $userDetails['LastActivityID'] );

$lastUpdate = date( "U", strtotime( $retVal['LastActivity']['lastupdate'] ) );
$now = date( "U" );

$status = ( $lastUpdate+(10*60) ) > $now ? "Online" : "Offline";

$retVal['Status'] = $status;

//	Just from a security/polish point of view:
unset( $retVal['Friendship'] );
unset( $retVal['FriendReciprocation'] );

echo json_encode( $retVal );
