<?php
//echo "Hold on..."; exit;

require_once('_db_secrets.php');		//	server-specific passwords

$g_pageLoadAt = microtime( true );
$g_numQueries = 0;

if( isset( $_SERVER[ "SERVER_NAME" ] ) )
{
    define( "AT_HOST", ( $_SERVER[ "SERVER_NAME" ] ) );
    //	Note: null domain should be used for localhost stuff (Chrome workaround)
    define( "AT_HOST_DOT", ( stristr( $_SERVER[ "SERVER_NAME" ], "retroachievements.org" ) ) ? '.retroachievements.org' : null  );
}
else
{
    define( "AT_HOST", "Internal" );
    define( "AT_HOST_DOT", null );
}

define( "CSS_FILE", "/css/style54.css" );

define( "DUMP_SQL", FALSE );
define( "PROFILE_SQL", FALSE );

function IsAtHome()
{
	//	Deprecated, no longer used.
    return FALSE;
}

@$db = mysqli_connect( "localhost", MYSQL_CONFIG_USER, MYSQL_CONFIG_PASS, "RACore" );

if( !$db )
{
    echo 'Error: Could not connect to database. Please try again later.';
    echo mysqli_error( $db );
    exit;
}

require_once("facebook.php");
$fbConfig = array();
$fbConfig[ 'appId' ] = '490904194261313';
$fbConfig[ 'secret' ] = FB_CONFIG_SECRET;
$fbConfig[ 'appToken' ] = $fbConfig[ 'appId' ] . '|' . $fbConfig[ 'secret' ];
$fbConn = new Facebook( $fbConfig );

$developerCountBoundaries = array( 5, 10, 50, 100, 200, 400, 600, 800, 1000, 2000, 3000, 4000, 5000, 6000 );
$developerPointBoundaries = array( 100, 200, 300, 500, 800, 1000, 1500, 2000, 3000, 4000, 5000, 10000, 15000, 20000, 30000, 40000, 50000, 60000, 70000 );
abstract class Permissions
{
    const Spam = -2;
    const Banned = -1;
    const Unregistered = 0;
    const Registered = 1;
    const SuperUser = 2;
    const Developer = 3;
    const Admin = 4;
    const Root = 5;
}

function PermissionsToString( $permissions )
{
    $permissionsStr = array( "Spam", "Banned", "Unregistered", "Registered", "Super User", "Developer", "Admin", "Root" );
    return $permissionsStr[ $permissions - (Permissions::Spam) ]; //	Offset of 0
}

abstract class ActivityType
{
    const Unknown = 0;
    const EarnedAchivement = 1;
    const Login = 2;
    const StartedPlaying = 3;
    const UploadAchievement = 4;
    const EditAchievement = 5;
    const CompleteGame = 6;
    const NewLeaderboardEntry = 7;
    const ImprovedLeaderboardEntry = 8;

}

//	Dynamic typing, misc usage
abstract class ObjectType
{
    const Game = 1;
    const User = 2;
    const Achievement = 3;

}

require_once('_utils.php');				//	general utilities
require_once('_dynrender.php');			//	dynamic rendering

require_once('_db_achievement.php');	//	achievement interfaces
require_once('_db_game.php');			//	game interfaces
require_once('_db_lbs.php');			//	leaderboard interfaces
require_once('_db_user.php');			//	user interfaces
require_once('_db_friend.php');			//	friend system
require_once('_db_message.php');		//	messaging system
require_once('_db_static.php');			//	static data interfaces
require_once('_db_news.php');			//	news interfaces
require_once('_db_forum.php');			//	forum interfaces
require_once('_db_tickets.php');		//	ticketing interfacing for bug reports
require_once('_db_activity.php');		//	activity system
require_once('_db_history.php');		//	historical data access
require_once('_db_utility.php');		//	db access utilities
require_once('_db_rating.php');			//	rating functions

$mobileBrowser = IsMobileBrowser();