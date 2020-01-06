<?php
require_once(__DIR__ . '/../vendor/autoload.php');

require_once(__DIR__ . '/database/achievement.php');
require_once(__DIR__ . '/database/activity.php');
require_once(__DIR__ . '/database/auth.php');
require_once(__DIR__ . '/database/codenote.php');
require_once(__DIR__ . '/database/console.php');
require_once(__DIR__ . '/database/forum.php');
require_once(__DIR__ . '/database/friend.php');
require_once(__DIR__ . '/database/game.php');
require_once(__DIR__ . '/database/history.php');
require_once(__DIR__ . '/database/leaderboard.php');
require_once(__DIR__ . '/database/message.php');
require_once(__DIR__ . '/database/news.php');
require_once(__DIR__ . '/database/playlist.php');
require_once(__DIR__ . '/database/rating.php');
require_once(__DIR__ . '/database/release.php');
require_once(__DIR__ . '/database/rom.php');
require_once(__DIR__ . '/database/search.php');
require_once(__DIR__ . '/database/setrequest.php');
require_once(__DIR__ . '/database/static.php');
require_once(__DIR__ . '/database/subscription.php');
require_once(__DIR__ . '/database/ticket.php');
require_once(__DIR__ . '/database/user.php');

require_once(__DIR__ . '/render/achievement.php');
require_once(__DIR__ . '/render/activity.php');
require_once(__DIR__ . '/render/auth.php');
require_once(__DIR__ . '/render/chat.php');
require_once(__DIR__ . '/render/codenote.php');
require_once(__DIR__ . '/render/comment.php');
require_once(__DIR__ . '/render/content.php');
require_once(__DIR__ . '/render/error.php');
require_once(__DIR__ . '/render/facebook.php');
require_once(__DIR__ . '/render/forum.php');
require_once(__DIR__ . '/render/friend.php');
require_once(__DIR__ . '/render/game.php');
require_once(__DIR__ . '/render/google.php');
require_once(__DIR__ . '/render/layout.php');
require_once(__DIR__ . '/render/leaderboard.php');
require_once(__DIR__ . '/render/news.php');
require_once(__DIR__ . '/render/static.php');
require_once(__DIR__ . '/render/subscription.php');
require_once(__DIR__ . '/render/tooltip.php');
require_once(__DIR__ . '/render/twitch.php');
require_once(__DIR__ . '/render/user.php');

require_once(__DIR__ . '/util/array.php');
require_once(__DIR__ . '/util/bbcode.php');
require_once(__DIR__ . '/util/bit.php');
require_once(__DIR__ . '/util/cookie.php');
require_once(__DIR__ . '/util/database.php');
require_once(__DIR__ . '/util/date.php');
require_once(__DIR__ . '/util/debug.php');
require_once(__DIR__ . '/util/environment.php');
require_once(__DIR__ . '/util/facebook.php');
require_once(__DIR__ . '/util/image.php');
require_once(__DIR__ . '/util/log.php');
require_once(__DIR__ . '/util/mail.php');
require_once(__DIR__ . '/util/mobilebrowser.php');
require_once(__DIR__ . '/util/permissions.php');
require_once(__DIR__ . '/util/recaptcha.php');
require_once(__DIR__ . '/util/request.php');
require_once(__DIR__ . '/util/string.php');
require_once(__DIR__ . '/util/trigger.php');
require_once(__DIR__ . '/util/upload.php');
require_once(__DIR__ . '/util/utf8.php');

$dotenv = new Dotenv\Dotenv(__DIR__ . '/../');
$dotenv->load();

// // set the user error handler method to be error_handler
// set_error_handler('error_handler', E_ALL);
// // error handler function
// function error_handler($errNo, $errStr, $errFile, $errLine)
// {
//     // clear any output that has already been generated
//     if (ob_get_length()) {
//         ob_clean();
//     }
//     // output the error message
//     $error_message = 'ERRNO: ' . $errNo . chr(10) .
//         'TEXT: ' . $errStr . chr(10) .
//         'LOCATION: ' . $errFile .
//         ', line ' . $errLine;
//     echo $error_message;
//     // prevent processing any more PHP scripts
//     exit;
// }

$g_pageLoadAt = microtime(true);
$g_numQueries = 0;
$_profileTimer = microtime(true);
$_loadDuration = 0;
ProfileStamp(); //Start ticking

if (isset($_SERVER["SERVER_NAME"])) {
    define("AT_HOST", ($_SERVER["SERVER_NAME"]));
    //	Note: null domain should be used for localhost stuff (Chrome workaround)
    define("AT_HOST_DOT", (mb_stristr($_SERVER["SERVER_NAME"], "retroachievements.org")) ? '.retroachievements.org' : null);
} else {
    define("AT_HOST", "Internal");
    define("AT_HOST_DOT", null);
}

define("VERSION", "1.29.1");

define("DUMP_SQL", false);
define("PROFILE_SQL", false);

try {
    $db = mysqli_connect(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'), getenv('DB_DATABASE'), getenv('DB_PORT'));
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

// $mobileBrowser = IsMobileBrowser();

$developerCountBoundaries = [
    5,
    10,
    50,
    100,
    200,
    400,
    600,
    800,
    1000,
    2000,
    3000,
    4000,
    5000,
    6000,
];
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
