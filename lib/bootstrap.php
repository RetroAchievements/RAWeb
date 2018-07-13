<?php
// header(($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' ? 'HTTP/1.1' : 'HTTP/1.0') . ' 503 Service Unavailable', true, 503);
// header("Retry-After: 3600");
// die('Maintenance. Please try again in a few minutes');

require_once( __DIR__ . '/../vendor/autoload.php' );

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$g_pageLoadAt = microtime(true);
$g_numQueries = 0;

if (isset($_SERVER["SERVER_NAME"])) {
	define("AT_HOST", ( $_SERVER["SERVER_NAME"] ));
	//	Note: null domain should be used for localhost stuff (Chrome workaround)
	define("AT_HOST_DOT", ( stristr($_SERVER["SERVER_NAME"], "retroachievements.org") ) ? '.retroachievements.org' : null);
} else {
	define("AT_HOST", "Internal");
	define("AT_HOST_DOT", null);
}

define("CSS_FILE", "/css/style54.css");

define("DUMP_SQL", FALSE);
define("PROFILE_SQL", FALSE);

function IsAtHome()
{
	//	Deprecated, no longer used.
	return FALSE;
}

try {
	$db = mysqli_connect(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_DATABASE'), getenv('DB_PORT'));
} catch (Exception $exception) {
	if (getenv('APP_ENV') === 'local') {
		throw $exception;
	} else {
		echo 'Error: Could not connect to database. Please try again later.';
		echo mysqli_error($db);
		exit;
	}
}

require_once( "facebook/facebook.php" );
$fbConfig = [];
$fbConfig['appId'] = getenv('FACEBOOK_APP_ID');
$fbConfig['secret'] = getenv('FACEBOOK_SECRET');
$fbConfig['appToken'] = $fbConfig['appId'] . '|' . $fbConfig['secret'];
$fbConn = new Facebook($fbConfig);

$developerCountBoundaries = [ 5, 10, 50, 100, 200, 400, 600, 800, 1000, 2000, 3000, 4000, 5000, 6000 ];
$developerPointBoundaries = [ 100, 200, 300, 500, 800, 1000, 1500, 2000, 3000, 4000, 5000, 10000, 15000, 20000, 30000, 40000, 50000, 60000, 70000 ];

require_once( 'utils.php' );                //	general utilities
require_once( 'permissions.php' );
require_once( 'recaptchalib.php' );
require_once( 'dynrender.php' );            //	dynamic rendering

require_once( 'database/achievement.php' );    //	achievement interfaces
require_once( 'database/game.php' );            //	game interfaces
require_once( 'database/lbs.php' );            //	leaderboard interfaces
require_once( 'database/user.php' );            //	user interfaces
require_once( 'database/friend.php' );            //	friend system
require_once( 'database/message.php' );        //	messaging system
require_once( 'database/static.php' );            //	static data interfaces
require_once( 'database/news.php' );            //	news interfaces
require_once( 'database/forum.php' );            //	forum interfaces
require_once( 'database/tickets.php' );        //	ticketing interfacing for bug reports
require_once( 'database/activity.php' );        //	activity system
require_once( 'database/history.php' );        //	historical data access
require_once( 'database/utility.php' );        //	db access utilities
require_once( 'database/rating.php' );            //	rating functions

$mobileBrowser = IsMobileBrowser();
