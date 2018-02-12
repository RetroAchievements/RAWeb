<?php
//	Internal: this is not public-facing!
require_once('../db.inc.php');

$gameID = seekGET( 'i' );

$gameRating = getGameRating( $gameID );

if( !isset( $gameRating[ObjectType::Game] ) )
{
	$gameRating[ObjectType::Game]['AvgPct'] = 0.0;
	$gameRating[ObjectType::Game]['NumVotes'] = 0;
}
if( !isset( $gameRating[ObjectType::Achievement] ) )
{
	$gameRating[ObjectType::Achievement]['AvgPct'] = 0.0;
	$gameRating[ObjectType::Achievement]['NumVotes'] = 0;
}

//error_log( $gameRating[ObjectType::Game] );

$gameData = array();
$gameData['GameID'] = $gameID;
$gameData['Ratings']['Game'] 		 		 = $gameRating[ ObjectType::Game ]['AvgPct'];
$gameData['Ratings']['Achievements'] 		 = $gameRating[ ObjectType::Achievement ]['AvgPct'];
$gameData['Ratings']['GameNumVotes'] 		 = $gameRating[ ObjectType::Game ]['NumVotes'];
$gameData['Ratings']['AchievementsNumVotes'] = $gameRating[ ObjectType::Achievement ]['NumVotes'];

//echo "header('Content-Type: application/json');";
echo json_encode( $gameData );
?>