<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . "/../src/RetroAchievementsWebApiClient.php";

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$errorCode = requestInputSanitized('e');

$apiUser = isset($user) ? $user : 'TestUser';
$apiKey = isset($user) ? GetAPIKey($user) : 'Your API Key';

RenderHtmlStart();
RenderHtmlHead("RetroAchievements API Demo (PHP)");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id='mainpage'>
    <div id='fullcontainer'>
        <?php
        echo '<style type="text/css">code { background-color: rgb(22,22,22); color: rgb(255, 255, 222); padding: 2px 6px;} </style>';

        echo "<h2 class='longheader'>RetroAchievements API Demo (PHP)</h2>";

        echo "<p>";
        // echo "This page will visually demonstrate some of the functionality of the RetroAchievements PHP API.";
        echo "Click <a href='/request/download-api-client.php'>here</a> to download an example PHP API client class.";
        // echo "Once added, you will be able to create a <code>RetroAchievements</code>
        // object and call functions on it, as demonstrated below. Note all <code>highlighted code</code> refers to a line of code in PHP.";
        echo "Please note, this is a beta offering and only supports read-only access to data that can already be found on the site.";
        echo "No personal/user data can be accessed using this API beyond what is already publicly accessible for every user (username, avatar, motto and activity).";
        echo "</p>";

        // echo "<h3 class='longheader'>Index:</h3>";
        //
        // echo "<div class='contentslist'>";
        // echo "<ul style='margin-left: 20px;'>";
        // echo "<li><a href='#ctor'>Initialize RetroAchievements object</a></li>";
        //
        // echo "<li>General</li>";
        // echo "<ul>";
        // echo "<li><a href='#GetTopTenUsers'>GetTopTenUsers()</a></li>";
        // echo "<li><a href='#GetConsoleIDs'>GetConsoleIDs()</a></li>";
        // echo "</ul>";
        //
        // echo "<li>Game</li>";
        // echo "<ul>";
        // echo "<li><a href='#GetGameList'>GetGameList( consoleID )</a></li>";
        // echo "<li><a href='#GetGameInfo'>GetGameInfo( gameID )</a></li>";
        // echo "<li><a href='#GetGameInfoExtended'>GetGameInfoExtended( gameID )</a></li>";
        // echo "<li><a href='#GetGameInfoAndUserProgress'>GetGameInfoAndUserProgress( user, gameID )</a></li>";
        // echo "</ul>";
        //
        // echo "<li>User</li>";
        // echo "<ul>";
        // echo "<li><a href='#GetUserRankAndScore'>GetUserRankAndScore( user )</a></li>";
        // echo "<li><a href='#GetUserRecentlyPlayedGames'>GetUserRecentlyPlayedGames( user, numGames )</a></li>";
        // echo "<li><a href='#GetUserProgress'>GetUserProgress( user, gamesCSV )</a></li>";
        // echo "<li><a href='#GetUserSummary'>GetUserSummary( user, numRecentGames )</a></li>";
        // echo "<li><a href='#GetFeedFor'>GetFeedFor( user, numRecentActivities )</a></li>";
        // echo "</ul>";
        //
        // echo "<li>Achievement</li>";
        // echo "<ul>";
        // echo "<li><a href='#GetAchievementsEarnedOnDay'>GetAchievementsEarnedOnDay( user, date )</a></li>";
        // echo "<li><a href='#GetAchievementsEarnedBetween'>GetAchievementsEarnedBetween( user, timeStart, timeEnd )</a></li>";
        // echo "</ul>";
        //
        // echo "</ul>";
        //
        // echo "</div>";

        echo '<b id=\'ctor\' class=\'longheader\' onclick="$(\'#ctorDiv\').toggle(); return false;" >Initialize Connection to RetroAchievements:</b>';
        echo '<div class=\'CodeDiv\' id=\'ctorDiv\'>';
        echo '<p>';
        // echo 'First, you must take a copy of the RA_API.php file, store it on your server, and in the file you wish to use, create an instance of the RetroAchievements object.
        //  This only needs to be done once per pageload. ';
        if (isset($user)) {
            echo '<b>Note: YOUR unique username and API Key are shown in the example below. Please keep your API Key safe, and do not share it! It is unique to your user account.</b>';
        }
        echo "</p>";
        echo '<code>require_once("RA_API.php");</code><br>';
        echo '<code>$RAConn = new RetroAchievements("' . $apiUser . '", "' . $apiKey . '");</code><br>';
        // $RAConn = new RetroAchievements("$apiUser", "$apiKey");
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetTopTenUsers\' class=\'longheader\' onclick="$(\'#GetTopTenUsersDiv\').toggle(); return false;" >Get Top Ten Users by Points:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetTopTenUsersDiv\'>';
        // echo "<p>Next, simply call one of the available functions to use it. Here we fetch the top 10 users on the global leaderboards:</p>";
        echo '<code>$data = $RAConn->GetTopTenUsers();</code><br>';
        // $data = $RAConn->GetTopTenUsers();
        // echo "<p>And here is the contents of that call. Note you are free to interpret this data, I am simply dumping it here so you can see what's in this object:</p>";
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetConsoleIDs\' class=\'longheader\' onclick="$(\'#GetConsoleIDsDiv\').toggle(); return false;" >Get Console IDs:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetConsoleIDsDiv\'>';
        echo '<code>$data = $RAConn->GetConsoleIDs();</code><br>';
        // $data = $RAConn->GetConsoleIDs();
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetGameList\' class=\'longheader\' onclick="$(\'#GetGameListDiv\').toggle(); return false;" >Get List of all Registered Original Gameboy Games (Console ID 4):</b>';
        echo '<div class=\'CodeDiv\' id=\'GetGameListDiv\'>';
        echo '<code>$data = $RAConn->GetGameList( 4 );</code>';
        // $data = $RAConn->GetGameList(4);
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetGameInfo\' class=\'longheader\' onclick="$(\'#GetGameInfoDiv\').toggle(); return false;" >Basic game information for Super Mario Land (GB) (Game ID 504):</b>';
        echo '<div class=\'CodeDiv\' id=\'GetGameInfoDiv\'>';
        echo '<code>$data = $RAConn->GetGameInfo( 504 );</code>';
        // $data = $RAConn->GetGameInfo(504);
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetGameInfoExtended\' class=\'longheader\' onclick="$(\'#GetGameInfoExtendedDiv\').toggle(); return false;" >Full game information for Super Mario Land (GB) (Game ID 504):</b>';
        echo '<div class=\'CodeDiv\' id=\'GetGameInfoExtendedDiv\'>';
        echo '<code>$data = $RAConn->GetGameInfoExtended( 504 );</code>';
        // $data = $RAConn->GetGameInfoExtended(504);
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetGameInfoAndUserProgress\' class=\'longheader\' onclick="$(\'#GetGameInfoAndUserProgressDiv\').toggle(); return false;" >Complete summary of Scott\'s progress in game ID 3:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetGameInfoAndUserProgressDiv\'>';
        echo '<code>$data = $RAConn->GetGameInfoAndUserProgress( \'Scott\', 3 );</code>';
        // $data = $RAConn->GetGameInfoAndUserProgress('Scott', 3);
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetUserRankAndScore\' class=\'longheader\' onclick="$(\'#GetUserRankAndScoreDiv\').toggle(); return false;" >Scott\'s global rank and score:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetUserRankAndScoreDiv\'>';
        echo '<code>$data = $RAConn->GetUserRankAndScore( \'Scott\' );</code>';
        // $data = $RAConn->GetUserRankAndScore('Scott');
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetUserRecentlyPlayedGames\' class=\'longheader\' onclick="$(\'#GetUserRecentlyPlayedGamesDiv\').toggle(); return false;" >Scott\'s 10 most recently played games:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetUserRecentlyPlayedGamesDiv\'>';
        echo '<code>$data = $RAConn->GetUserRecentlyPlayedGames( \'Scott\' );</code>';
        // $data = $RAConn->GetUserRecentlyPlayedGames('Scott', 10);
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetUserProgress\' class=\'longheader\' onclick="$(\'#GetUserProgressDiv\').toggle(); return false;" >Scott\'s progress on games with IDs 2, 3 and 75:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetUserProgressDiv\'>';
        echo '<code>$data = $RAConn->GetUserProgress( \'Scott\', \'2, 3, 75\' );</code>';
        // $data = $RAConn->GetUserProgress('Scott', '2, 3, 75');
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetUserSummary\' class=\'longheader\' onclick="$(\'#GetUserSummaryDiv\').toggle(); return false;" >User summary of Scott, and 3 most recently played games:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetUserSummaryDiv\'>';
        echo '<code>$data = $RAConn->GetUserSummary( \'Scott\', 3 );</code>';
        // $data = $RAConn->GetUserSummary('Scott', 3);
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        // echo '<b id=\'GetFeedFor\' class=\'longheader\' onclick="$(\'#GetFeedForDiv\').toggle(); return false;" >Scott\'s latest feed (last 10 activities):</b>';
        // echo '<div class=\'CodeDiv\' id=\'GetFeedForDiv\'>';
        // echo '<code>$data = $RAConn->GetFeedFor( \'Scott\', 10 );</code>';
        // $data = $RAConn->GetFeedFor( 'Scott', 10 );
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        // echo ProfileStamp( NULL, true );
        // echo '</div>';

        echo '<b id=\'GetAchievementsEarnedOnDay\' class=\'longheader\' onclick="$(\'#GetAchievementsEarnedOnDayDiv\').toggle(); return false;" >Get Achievements Earned by Scott on January 4th 2014:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetAchievementsEarnedOnDayDiv\'>';
        echo '<code>$data = $RAConn->GetAchievementsEarnedOnDay( \'Scott\', \'2014-01-04\' );</code>';
        // $data = $RAConn->GetAchievementsEarnedOnDay('Scott', '2014-01-04');
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetAchievementsEarnedBetween\' class=\'longheader\' onclick="$(\'#GetAchievementsEarnedBetweenDiv\').toggle(); return false;" >Get Achievements Earned by Scott overnight on New Years Eve:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetAchievementsEarnedBetweenDiv\'>';
        echo '<code>$data = $RAConn->GetAchievementsEarnedBetween( \'Scott\', \'2013-12-31 20:00:00\', \'2014-01-01 04:00:00\' );</code>';
        // $data = $RAConn->GetAchievementsEarnedBetween('Scott', '2013-12-31 20:00:00', '2014-01-01 04:00:00');
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';

        echo '<b id=\'GetUserCompletedGames\' class=\'longheader\' onclick="$(\'#GetUserCompletedGamesDiv\').toggle(); return false;" >Get Games Completed by Scott:</b>';
        echo '<div class=\'CodeDiv\' id=\'GetUserCompletedGamesDiv\'>';
        echo '<code>$data = $RAConn->GetUserCompletedGames( \'Scott\' );</code>';
        // $data = $RAConn->GetUserCompletedGames('Scott');
        // echo "<pre>".json_encode($data)."</pre>";
        // echo "<a href='#'>Back to top</a>";
        echo '</div>';
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
