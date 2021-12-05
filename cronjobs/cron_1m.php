<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

function GetNextHighestGameID($givenID)
{
    $query = "SELECT MIN(ID) AS NextID FROM GameData
				  WHERE ID > $givenID";

    $dbResult = s_mysql_query($query);

    $data = mysqli_fetch_assoc($dbResult);
    if ($data['NextID'] == null) {
        return 1;
    } else {
        return $data['NextID'];
    }
}

function GetNextHighestUserID($givenID)
{
    $query = "SELECT MIN(ID) AS NextID FROM UserAccounts
				  WHERE ID > $givenID AND RAPoints > 0";

    $dbResult = s_mysql_query($query);

    $data = mysqli_fetch_assoc($dbResult);
    if ($data['NextID'] == null) {
        return 1;
    } else {
        return $data['NextID'];
    }
}

$staticData = getStaticData();

$gameID = $staticData['NextGameToScan'];
for ($i = 0; $i < 3; $i++) {
    recalculateTrueRatio($gameID);
    $gameID = GetNextHighestGameID($gameID);
}
static_setnextgametoscan($gameID);

$userID = $staticData['NextUserIDToScan'];
$user = '';
for ($i = 0; $i < 3; $i++) {
    $user = getUserFromID($userID);
    recalcScore($user);
    $userID = GetNextHighestUserID($userID);
}
static_setnextusertoscan($userID);

$date = date('Y/m/d H:i:s');
echo "[$date] cron_1m run, game ID now $gameID, user now at $userID ($user)\r\n";
