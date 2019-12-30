<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$gameID = seekGET( 'g' );
$targetUser = seekGET( 'u' );
getGameMetadata( $gameID, $targetUser, $achData, $gameData );

foreach ($achData as &$achievement) {
    $achievement['MemAddr'] = md5($achievement['MemAddr']);
}
$gameData['Achievements'] = $achData;
$gameData['RichPresencePatch'] = md5($gameData['RichPresencePatch']);

$gameData['NumAwardedToUser'] = 0;
$gameData['NumAwardedToUserHardcore'] = 0;

if(!empty($achData)) {
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
}

$gameData['UserCompletion'] = 0;
$gameData['UserCompletionHardcore'] = 0;
if( $gameData['NumAchievements'] > 0 )
{
	$gameData['UserCompletion'] = sprintf( "%01.2f%%", ($gameData['NumAwardedToUser']/$gameData['NumAchievements'])*100.0 );
	$gameData['UserCompletionHardcore'] = sprintf( "%01.2f%%", ($gameData['NumAwardedToUserHardcore']/$gameData['NumAchievements'])*100.0 );
}

echo json_encode( $gameData );
