<?php
//header(($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1' ? 'HTTP/1.1' : 'HTTP/1.0') . ' 503 Service Unavailable', true, 503);
//header("Retry-After: 3600");
//header('Content-Type: application/json');
//echo json_encode(['Success' => false, 'Error' => 'Maintenance. Please try again in a few minutes']);
//return false;

require_once(__DIR__ . '/../vendor/autoload.php');

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

$g_pageLoadAt = microtime(true);
$g_numQueries = 0;

if (isset($_SERVER["SERVER_NAME"])) {
    define("AT_HOST", ($_SERVER["SERVER_NAME"]));
    //	Note: null domain should be used for localhost stuff (Chrome workaround)
    define("AT_HOST_DOT",
        (stristr($_SERVER["SERVER_NAME"], "retroachievements.org")) ? '.retroachievements.org' : null);
} else {
    define("AT_HOST", "Internal");
    define("AT_HOST_DOT", null);
}

define("VERSION", "1.26.4");

define("CSS_FILE", "/css/style54.css?v=" . VERSION);

define("DUMP_SQL", false);
define("PROFILE_SQL", false);

function isAtHome()
{
    //	Deprecated, no longer used.
    return false;
}

try {
    $db = mysqli_connect(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_DATABASE'),
        getenv('DB_PORT'));
    mysqli_set_charset($db, 'latin1');
    mysqli_query($db, "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
} catch (Exception $exception) {
    if (getenv('APP_ENV') === 'local') {
        throw $exception;
    } else {
        echo 'Error: Could not connect to database. Please try again later.';
        echo mysqli_error($db);
        exit;
    }
}

require_once("facebook/facebook.php");
$fbConfig = [];
$fbConfig['appId'] = getenv('FACEBOOK_APP_ID');
$fbConfig['secret'] = getenv('FACEBOOK_SECRET');
$fbConfig['appToken'] = $fbConfig['appId'] . '|' . $fbConfig['secret'];
$fbConn = new Facebook($fbConfig);

$developerCountBoundaries = [5, 10, 50, 100, 200, 400, 600, 800, 1000, 2000, 3000, 4000, 5000, 6000];
$developerPointBoundaries = [
    100,
    200,
    300,
    500,
    800,
    1000,
    1500,
    2000,
    3000,
    4000,
    5000,
    10000,
    15000,
    20000,
    30000,
    40000,
    50000,
    60000,
    70000,
];

require_once('utils.php');
require_once('permissions.php');
require_once('recaptchalib.php');
require_once('dynrender.php');
require_once('mail.php');

require_once('database/achievement.php');
require_once('database/game.php');
require_once('database/lbs.php'); // leaderboards
require_once('database/user.php');
require_once('database/friend.php');
require_once('database/message.php');
require_once('database/static.php');
require_once('database/news.php');
require_once('database/forum.php');
require_once('database/tickets.php');
require_once('database/activity.php');
require_once('database/history.php');
require_once('database/utility.php');
require_once('database/release.php');
require_once('database/rating.php');
require_once('database/subscription.php');

$mobileBrowser = IsMobileBrowser();
