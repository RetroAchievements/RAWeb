<?php 
	require_once('/var/www/html/db.inc.php');

	function GetNextHighestGameID( $givenID )
	{
		$query = "SELECT MIN(ID) AS NextID FROM GameData
				  WHERE ID > $givenID";
				  
		$dbResult = s_mysql_query( $query );
		
		$data = mysql_fetch_assoc( $dbResult );
		if( $data['NextID'] == NULL )
			return 1;
		else 
			return $data['NextID'];
	}
	
	function GetNextHighestUserID( $givenID )
	{
		$query = "SELECT MIN(ID) AS NextID FROM UserAccounts
				  WHERE ID > $givenID";
				  
		$dbResult = s_mysql_query( $query );
		
		$data = mysql_fetch_assoc( $dbResult );
		if( $data['NextID'] == NULL )
			return 1;
		else 
			return $data['NextID'];
	}
	
	$staticData = getStaticData();
	
	$gameID = $staticData['NextGameToScan'];
	for( $i = 0; $i < 3; $i++ )
	{
		RecalculateTrueRatio( $gameID );
		$gameID = GetNextHighestGameID( $gameID );
	}
	static_setnextgametoscan( $gameID );
	
	
	$userID = $staticData['NextUserIDToScan'];
	$user = '';
	for( $i = 0; $i < 3; $i++ )
	{
		$user = getUserFromID( $userID );
		recalcScore( $user );
		$userID = GetNextHighestUserID( $userID );
	}
	static_setnextusertoscan( $userID );
	
	$date = date('Y/m/d H:i:s');
	echo "[$date] cron_1m run, game ID now $gameID, user now at $userID ($user)\r\n";
	
?>
