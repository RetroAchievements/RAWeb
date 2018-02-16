<?php
//	Internal: this is not public-facing!
require_once __DIR__ . '/../../lib/bootstrap.php';

if( !ValidateAPIKey( seekGET( 'z' ), seekGET( 'y' ) ) )
{
	echo "Invalid API Key";
	exit;
}

$user = seekGET( 'u', NULL );
$count = min( seekGET( 'c', 10 ), 50 );
$offset = seekGET( 'o', 0 );

$recentlyPlayedData = array();
$numRecentlyPlayed = getRecentlyPlayedGames( $user, $offset, $count, $recentlyPlayedData );

if( count( $recentlyPlayedData ) > 0 )
{
	$gameIDsCSV = $recentlyPlayedData[ 0 ][ 'GameID' ];
	for( $i = 1; $i < $numRecentlyPlayed; $i++ )
	{
		$gameIDsCSV .= ", " . $recentlyPlayedData[ $i ][ 'GameID' ];
	}

	getUserProgress( $user, $gameIDsCSV, $awardedData );

	$iter = 0;
	foreach( $awardedData as $nextAwardID => $nextAwardData )
	{
		$recentlyPlayedData[ $iter ][ 'NumPossibleAchievements' ] = $nextAwardData[ 'NumPossibleAchievements' ];
		$recentlyPlayedData[ $iter ][ 'PossibleScore' ] = $nextAwardData[ 'PossibleScore' ];
		$recentlyPlayedData[ $iter ][ 'NumAchieved' ] = $nextAwardData[ 'NumAchieved' ];
		$recentlyPlayedData[ $iter ][ 'ScoreAchieved' ] = $nextAwardData[ 'ScoreAchieved' ];
		$iter++;//	Assumes a LOT about the order of this array!
	}

	$libraryOut[ 'Awarded' ] = $awardedData;
}

echo json_encode( $recentlyPlayedData );
