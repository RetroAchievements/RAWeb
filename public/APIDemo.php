<?php
require_once __DIR__.'/../lib/bootstrap.php';

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	
RenderDocType();

$pageTitle = "RetroAchievements API Demo (PHP)";
$errorCode = seekGET( 'e' );

$apiUser = isset( $user ) ? $user : 'TestUser';
$apiKey = isset( $user ) ? GetAPIKey($user) : 'gdPk9A1UWj9IWCM9uuzcpcTSatwubnGh';

?>
<head>
	<?php RenderSharedHeader( $user ); ?>
	<?php RenderTitleTag( $pageTitle, $user ); ?>
	<?php RenderGoogleTracking(); ?>
</head>
<body onload="">

<?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

<div id='mainpage'>
<div class='both'>

<?php

echo '<style type="text/css">code { background-color:rgb(255, 255, 25); color: rgb(0,0,255); border:1px dotted; padding: 2px 8px; font-size: medium; line-height: 2em } </style>';

echo "<h2 class='longheader'>$pageTitle</h2>";

echo "<p>This page will visually demonstrate some of the functionality of the RetroAchievements PHP API. Click 
		<a href='/GetRA_API.php'>here</a> to download the necessary PHP file you will need
		to add to your server. Once added, you will be able to create a <code>RetroAchievements</code> 
		object and call functions on it, as demonstrated below. Note all <code>highlighted code</code> refers to a line of code in PHP.
		Please note, this is a beta offering and only supports read-only access to data that can already be found on the site. No personal/user
		data can be accessed using this API beyond what is already publically accessible for every user (username, avatar, motto and activity).</p>";

echo "<h3 class='longheader'>Index:</h3>";

echo "<div class='contentslist'>";
echo "<ul style='margin-left: 20px;'>";
echo "<li><a href='#ctor'>Initialize RetroAchievements object</a></li>";

echo "<li>General</li>";
echo "<ul>";
echo "<li><a href='#GetTopTenUsers'>GetTopTenUsers()</a></li>";
echo "<li><a href='#GetConsoleIDs'>GetConsoleIDs()</a></li>";
echo "</ul>";

echo "<li>Game</li>";
echo "<ul>";
echo "<li><a href='#GetGameList'>GetGameList( consoleID )</a></li>";
echo "<li><a href='#GetGameInfo'>GetGameInfo( gameID )</a></li>";
echo "<li><a href='#GetGameInfoExtended'>GetGameInfoExtended( gameID )</a></li>";
echo "<li><a href='#GetGameInfoAndUserProgress'>GetGameInfoAndUserProgress( user, gameID )</a></li>";
echo "</ul>";

echo "<li>User</li>";
echo "<ul>";
echo "<li><a href='#GetUserRankAndScore'>GetUserRankAndScore( user )</a></li>";
echo "<li><a href='#GetUserRecentlyPlayedGames'>GetUserRecentlyPlayedGames( user, numGames )</a></li>";
echo "<li><a href='#GetUserProgress'>GetUserProgress( user, gamesCSV )</a></li>";
echo "<li><a href='#GetUserSummary'>GetUserSummary( user, numRecentGames )</a></li>";
echo "<li><a href='#GetFeedFor'>GetFeedFor( user, numRecentActivities )</a></li>";
echo "</ul>";

echo "<li>Achievement</li>";
echo "<ul>";
echo "<li><a href='#GetAchievementsEarnedOnDay'>GetAchievementsEarnedOnDay( user, date )</a></li>";
echo "<li><a href='#GetAchievementsEarnedBetween'>GetAchievementsEarnedBetween( user, timeStart, timeEnd )</a></li>";
echo "</ul>";

echo "</ul>";

echo "</div>";

echo '<h3 id=\'ctor\' class=\'longheader\' onclick="$(\'#ctorDiv\').toggle(500); return false;" >Initialize Connection to RetroAchievements:</h3>';
echo '<div class=\'CodeDiv\' id=\'ctorDiv\'>';
echo '<p>First, you must take a copy of the RA_API.php file, store it on your server, and in the file you wish to use, create an instance of the RetroAchievements object.
		 This only needs to be done once per pageload. ';
if( isset( $user ) )
	echo '<b>Note: YOUR unique username and API Key are being shown for you below. Please your API Key safe, and do not share it! It is unique to your user account.</b>';

echo "</p>";
echo '<code>require_once( "RA_API.php" );</code><br/>';
echo '<code>$RAConn = new RetroAchievements(\'' . $apiUser . '\' \'' . $apiKey . '\' );</code><br/>';
require_once( "RA_API.php" );
$RAConn = new RetroAchievements( "$apiUser", "$apiKey" );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetTopTenUsers\' class=\'longheader\' onclick="$(\'#GetTopTenUsersDiv\').toggle(500); return false;" >Get Top Ten Users by Points:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetTopTenUsersDiv\'>';
echo "<p>Next, simply call one of the available functions to use it. Here we fetch the top 10 users on the global leaderboards:</p>";
echo '<code>$data = $RAConn->GetTopTenUsers();</code><br/>';
$data = $RAConn->GetTopTenUsers();
echo "<p>And here is the contents of that call. Note you are free to interpret this data, I am simply dumping it here so you can see what's in this object:</p>";
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';


echo '<h3 id=\'GetConsoleIDs\' class=\'longheader\' onclick="$(\'#GetConsoleIDsDiv\').toggle(500); return false;" >Get Console IDs:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetConsoleIDsDiv\'>';
echo '<code>$data = $RAConn->GetConsoleIDs();</code><br/>';
$data = $RAConn->GetConsoleIDs();
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';


echo '<h3 id=\'GetGameList\' class=\'longheader\' onclick="$(\'#GetGameListDiv\').toggle(500); return false;" >Get List of all Registered Original Gameboy Games (Console ID 4):</h3>';
echo '<div class=\'CodeDiv\' id=\'GetGameListDiv\'>';
echo '<code>$data = $RAConn->GetGameList( 4 );</code>';
$data = $RAConn->GetGameList( 4 );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetGameInfo\' class=\'longheader\' onclick="$(\'#GetGameInfoDiv\').toggle(500); return false;" >Basic game information for Super Mario Land (GB) (Game ID 504):</h3>';
echo '<div class=\'CodeDiv\' id=\'GetGameInfoDiv\'>';
echo '<code>$data = $RAConn->GetGameInfo( 504 );</code>';
$data = $RAConn->GetGameInfo( 504 );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetGameInfoExtended\' class=\'longheader\' onclick="$(\'#GetGameInfoExtendedDiv\').toggle(500); return false;" >Full game information for Super Mario Land (GB) (Game ID 504):</h3>';
echo '<div class=\'CodeDiv\' id=\'GetGameInfoExtendedDiv\'>';
echo '<code>$data = $RAConn->GetGameInfoExtended( 504 );</code>';
$data = $RAConn->GetGameInfoExtended( 504 );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetGameInfoAndUserProgress\' class=\'longheader\' onclick="$(\'#GetGameInfoAndUserProgressDiv\').toggle(500); return false;" >Complete summary of Scott\'s progress in game ID 3:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetGameInfoAndUserProgressDiv\'>';
echo '<code>$data = $RAConn->GetGameInfoAndUserProgress( \'Scott\', 3 );</code>';
$data = $RAConn->GetGameInfoAndUserProgress( 'Scott', 3 );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';


echo '<h3 id=\'GetUserRankAndScore\' class=\'longheader\' onclick="$(\'#GetUserRankAndScoreDiv\').toggle(500); return false;" >Scott\'s global rank and score:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetUserRankAndScoreDiv\'>';
echo '<code>$data = $RAConn->GetUserRankAndScore( \'Scott\' );</code>';
$data = $RAConn->GetUserRankAndScore( 'Scott' );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetUserRecentlyPlayedGames\' class=\'longheader\' onclick="$(\'#GetUserRecentlyPlayedGamesDiv\').toggle(500); return false;" >Scott\'s 10 most recently played games:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetUserRecentlyPlayedGamesDiv\'>';
echo '<code>$data = $RAConn->GetUserRecentlyPlayedGames( \'Scott\' );</code>';
$data = $RAConn->GetUserRecentlyPlayedGames( 'Scott', 10 );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetUserProgress\' class=\'longheader\' onclick="$(\'#GetUserProgressDiv\').toggle(500); return false;" >Scott\'s progress on games with IDs 2, 3 and 75:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetUserProgressDiv\'>';
echo '<code>$data = $RAConn->GetUserProgress( \'Scott\', \'2, 3, 75\' );</code>';
$data = $RAConn->GetUserProgress( 'Scott', '2, 3, 75' );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetUserSummary\' class=\'longheader\' onclick="$(\'#GetUserSummaryDiv\').toggle(500); return false;" >User summary of Scott, and 3 most recently played games:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetUserSummaryDiv\'>';
echo '<code>$data = $RAConn->GetUserSummary( \'Scott\', 3 );</code>';
$data = $RAConn->GetUserSummary( 'Scott', 3 );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetFeedFor\' class=\'longheader\' onclick="$(\'#GetFeedForDiv\').toggle(500); return false;" >Scott\'s latest feed (last 10 activities):</h3>';
echo '<div class=\'CodeDiv\' id=\'GetFeedForDiv\'>';
echo '<code>$data = $RAConn->GetFeedFor( \'Scott\', 10 );</code>';
$data = $RAConn->GetFeedFor( 'Scott', 10 );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';


echo '<h3 id=\'GetAchievementsEarnedOnDay\' class=\'longheader\' onclick="$(\'#GetAchievementsEarnedOnDayDiv\').toggle(500); return false;" >Get Achievements Earned by Scott on January 4th 2014:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetAchievementsEarnedOnDayDiv\'>';
echo '<code>$data = $RAConn->GetAchievementsEarnedOnDay( \'Scott\', \'2014-01-04\' );</code>';
$data = $RAConn->GetAchievementsEarnedOnDay( 'Scott', '2014-01-04' );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';

echo '<h3 id=\'GetAchievementsEarnedBetween\' class=\'longheader\' onclick="$(\'#GetAchievementsEarnedBetweenDiv\').toggle(500); return false;" >Get Achievements Earned by Scott overnight on New Years Eve:</h3>';
echo '<div class=\'CodeDiv\' id=\'GetAchievementsEarnedBetweenDiv\'>';
echo '<code>$data = $RAConn->GetAchievementsEarnedBetween( \'Scott\', \'2013-12-31 20:00:00\', \'2014-01-01 04:00:00\' );</code>';
$data = $RAConn->GetAchievementsEarnedBetween( 'Scott', '2013-12-31 20:00:00', '2014-01-01 04:00:00' );
new dBug( $data );
echo "<a href='#'>Back to top</a>";
echo ProfileStamp( NULL, true );
echo '</div>';



?>

<small>NB. This page uses the GPL'd software <a href='http://dbug.ospinto.com/'>dBug</a> to demonstrate sample data.</small><br/>

</div>
</div>

<?php RenderFooter(); ?>

</body>
</html>
