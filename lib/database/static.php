<?php
require_once( __DIR__ . '/../bootstrap.php' );

//////////////////////////////////////////////////////////////////////////////////////////
//	Static Data stubs/functs
//////////////////////////////////////////////////////////////////////////////////////////


//	00:47 24/04/2013
function static_addnewachievement( $id )
{
	$query = "UPDATE StaticData AS sd ";
	$query.= "SET sd.NumAchievements=sd.NumAchievements+1, sd.LastCreatedAchievementID='$id'";
	log_sql( $query );
	$dbResult = s_mysql_query( $query );
	if( $dbResult == FALSE )
	{
		//	ONLY if it goes wrong, report an error.
		error_log( __FUNCTION__ );
		error_log( $query );
	}
}

//	00:47 24/04/2013
function static_addnewgame( $id )
{
	$query = "UPDATE StaticData AS sd ";
	$query.= "SET sd.NumGames = sd.NumGames+1, sd.LastCreatedGameID = '$id'";
	log_sql( $query );
	$dbResult = s_mysql_query( $query );
	if( $dbResult == FALSE )
	{
		//	ONLY if it goes wrong, report an error.
		error_log( __FUNCTION__ );
		error_log( $query );
	}
}

//	00:47 24/04/2013
function static_addnewregistereduser( $user )
{
	$query = "UPDATE StaticData AS sd ";
	$query.= "SET sd.NumRegisteredUsers = sd.NumRegisteredUsers+1, sd.LastRegisteredUser = '$user', sd.LastRegisteredUserAt = NOW()";
	log_sql( $query );
	$dbResult = s_mysql_query( $query );
	if( $dbResult == FALSE )
	{
		//	ONLY if it goes wrong, report an error.
		error_log( __FUNCTION__ );
		error_log( $query );
	}
}

//	00:47 24/04/2013
function static_setlastearnedachievement( $id, $user, $points )
{
	$query = "UPDATE StaticData AS sd ";
	$query.= "SET sd.NumAwarded = sd.NumAwarded+1, sd.LastAchievementEarnedID = '$id', sd.LastAchievementEarnedByUser = '$user', sd.LastAchievementEarnedAt = NOW(), sd.TotalPointsEarned=sd.TotalPointsEarned+$points";
	log_sql( $query );
	$dbResult = s_mysql_query( $query );
	if( $dbResult == FALSE )
	{
		//	ONLY if it goes wrong, report an error.
		error_log( __FUNCTION__ );
		error_log( $query );
	}
}

//	00:47 24/04/2013
function static_setlastupdatedgame( $id )
{
	$query = "UPDATE StaticData AS sd ";
	$query.= "SET sd.LastUpdatedGameID = '$id'";
	log_sql( $query );
	$dbResult = s_mysql_query( $query );
	if( $dbResult == FALSE )
	{
		//	ONLY if it goes wrong, report an error.
		error_log( __FUNCTION__ );
		error_log( $query );
	}
}

//	00:49 24/04/2013
function static_setlastupdatedachievement( $id )
{
	$query = "UPDATE StaticData AS sd ";
	$query.= "SET sd.LastUpdatedAchievementID = '$id'";
	log_sql( $query );
	$dbResult = s_mysql_query( $query );
	if( $dbResult == FALSE )
	{
		//	ONLY if it goes wrong, report an error.
		error_log( __FUNCTION__ );
		error_log( $query );
	}
}

function static_setnextgametoscan( $gameID )
{
	$query = "UPDATE StaticData AS sd
			  SET sd.NextGameToScan = '$gameID'";
	$dbResult = s_mysql_query( $query );
	
	SQL_ASSERT( $dbResult );
}

function static_setnextusertoscan( $userID )
{
	$query = "UPDATE StaticData AS sd
			  SET sd.NextUserIDToScan = '$userID'";
	$dbResult = s_mysql_query( $query );
	
	SQL_ASSERT( $dbResult );
}
