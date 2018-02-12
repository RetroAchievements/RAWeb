<?php
//	Internal: this is not public-facing!
require_once('../db.inc.php');

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$gameID = seekGET( 'g' );
$targetUser = seekGET( 'u' );
getGameMetadata( $gameID, $targetUser, $achData, $gameData );

$gameData['Achievements'] = $achData;

$gameData['NumAwardedToUser'] = 0;
$gameData['NumAwardedToUserHardcore'] = 0;
foreach( $achData as $nextAch )
{
	if( isset( $nextAch['DateEarned'] ) )
    {
		$gameData['NumAwardedToUser'] += 1;
    }
	if( isset( $nextAch['DateEarnedHardcore'] ) )
    {
		$gameData['NumAwardedToUserHardcore'] += 1;
    }
}

$gameData['UserCompletion'] = 0;
$gameData['UserCompletionHardcore'] = 0;
if( $gameData['NumAchievements'] > 0 )
{
	$gameData['UserCompletion'] = sprintf( "%01.2f%%", ($gameData['NumAwardedToUser']/$gameData['NumAchievements'])*100.0 );
	$gameData['UserCompletionHardcore'] = sprintf( "%01.2f%%", ($gameData['NumAwardedToUserHardcore']/$gameData['NumAchievements'])*100.0 );
}

echo json_encode( $gameData );