<?php
require_once('db.inc.php');

//$onlineList = getCurrentlyOnlinePlayers();
$ingameList = getLatestRichPresenceUpdates();

//error_log( count( $onlineList ) );
//error_log( count( $ingameList ) );

$mergedList = array();
//foreach( $onlineList as $playerOnline )
//{
//    $mergedList[ $playerOnline[ 'User' ] ] = $playerOnline;
//    $mergedList[ $playerOnline[ 'User' ] ][ 'InGame' ] = false;
//}

foreach( $ingameList as $playerIngame )
{
    //	Array merge/overwrite
    $mergedList[ $playerIngame[ 'User' ] ] = $playerIngame;
    $mergedList[ $playerIngame[ 'User' ] ][ 'InGame' ] = true;
}

$finalList = array();
foreach( $mergedList as $mergedItem )
{
    $finalList[] = $mergedItem;
}

//error_log( count( $finalList ) );

echo json_encode( $finalList );
