<?php
require_once __DIR__ . '/../lib/bootstrap.php';

/*
  DONT FORGET! All URLS within Game, User or Achievement MUST have an extra forward slash
  as they are all in a pseudo-directory of /Game etc.
 */

$gameID = seekGET( 'ID' );
settype( $gameID, 'integer' );
if( $gameID == NULL || $gameID == 0 )
{
    header( "Location: " . getenv('APP_URL') . "?e=urlissue" );
    exit;
}

$friendScores = Array();
if( RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions ) )
{
    getAllFriendsProgress( $user, $gameID, $friendScores );
}

$errorCode = seekGET( 'e' );

$flags = seekGET( 'f', 3 ); // flags = 3 means Core achievements
settype( $flags, 'integer' );

$defaultSort = 1;
if( isset( $user ) )
{
    $defaultSort = 13;
}
$sortBy = seekGET( 's', $defaultSort );

if( !isset( $user ) && ( $sortBy == 3 || $sortBy == 13 ) )
    $sortBy = 1;


$numAchievements = getGameMetadataByFlags( $gameID, $user, $achievementData, $gameData, $sortBy, NULL, $flags );

if( !isset($gameData) )
{
	echo "Invalid game ID!";
	exit;
}

$gameAlts = GetGameAlternatives( $gameID );

$numDistinctPlayersCasual = $gameData[ 'NumDistinctPlayersCasual' ];
$numDistinctPlayersHardcore = $gameData[ 'NumDistinctPlayersHardcore' ];
if( $numDistinctPlayersCasual == 0 )
{
    $numDistinctPlayersCasual = 1;
}
if( $numDistinctPlayersHardcore == 0 )
{
    $numDistinctPlayersHardcore = 1; //??
}

$gameTitle = $gameData[ 'Title' ];
$consoleName = $gameData[ 'ConsoleName' ];
$consoleID = $gameData[ 'ConsoleID' ];
$forumTopicID = $gameData[ 'ForumTopicID' ];
$richPresenceData = $gameData[ 'RichPresencePatch' ];

//	Get the top ten players at this game:
$gameTopAchievers = getGameTopAchievers( $gameID, 0, 10, $user );

$totalUniquePlayers = getTotalUniquePlayers( $gameID );
if( $numDistinctPlayersCasual < $totalUniquePlayers )
    $numDistinctPlayersCasual = $totalUniquePlayers;
if( $numDistinctPlayersHardcore < $totalUniquePlayers )
    $numDistinctPlayersHardcore = $totalUniquePlayers;

$achDist = getAchievementDistribution( $gameID, 0 ); //	for now, only retrieve casual!
for( $i = 1; $i <= $numAchievements; $i++ )
{
    if( !array_key_exists( $i, $achDist ) )
        $achDist[ $i ] = 0;
}

ksort( $achDist );
//var_dump( $achDist );

$numArticleComments = getArticleComments( 1, $gameID, 0, 20, $commentData );

$pageTitle = "$gameTitle ($consoleName)";
getCookie( $user, $cookie );

$numLeaderboards = getLeaderboardsForGame( $gameID, $lbData, $user );

//var_dump( $lbData );

$screenshotWidth = 200;
$screenshotHeight = 133;
if( $consoleID == 1 ) //md
{
    $screenshotHeight = 150; //129;
}
else if( $consoleID == 3 ) //snes
{
    $screenshotHeight = 175;
}
else if( $consoleID == 4 ) //gb
{
    $screenshotHeight = 180;
}
else if( $consoleID == 5 ) // gba
{
    $screenshotHeight = 133;
}
else if( $consoleID == 6 ) // gbc
{
    $screenshotHeight = 180;
}

//	Quickly calculate earned/potential
$totalEarnedCasual = 0;
$totalEarnedHardcore = 0;
$numEarnedCasual = 0;
$numEarnedHardcore = 0;
$totalPossible = 0;

$totalEarnedTrueRatio = 0;
$totalPossibleTrueRatio = 0;

$authors = [];
if( isset( $achievementData ) )
{
    //var_dump( $achievementData );
    foreach( $achievementData as &$nextAch )
    {
        $authors[strtolower($nextAch[ 'Author' ])] = $nextAch[ 'Author' ];
        $totalPossible += $nextAch[ 'Points' ];
        $totalPossibleTrueRatio += $nextAch[ 'TrueRatio' ];

        if( isset( $nextAch[ 'DateEarned' ] ) )
        {
            $numEarnedCasual++;
            $totalEarnedCasual += $nextAch[ 'Points' ];
            $totalEarnedTrueRatio += $nextAch[ 'TrueRatio' ];
        }
        if( isset( $nextAch[ 'DateEarnedHardcore' ] ) )
        {
            $numEarnedHardcore++;
            $totalEarnedHardcore += $nextAch[ 'Points' ];
        }
    }
    ksort($authors);
}


RenderDocType( TRUE );
?>

<head prefix="og: http://ogp.me/ns# retroachievements: http://ogp.me/ns/apps/retroachievements#">

    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">

        // Load the Visualization API and the piechart package.
        google.load('visualization', '1.0', {'packages': ['corechart']});

        // Set a callback to run when the Google Visualization API is loaded.
        google.setOnLoadCallback(drawCharts);

        // Callback that creates and populates a data table,
        // instantiates the pie chart, passes in the data and
        // draws it.
        function drawCharts()
        {
            var dataTotalScore = new google.visualization.DataTable();

            // Declare columns
            dataTotalScore.addColumn('number', 'Total Achievements Won');
            dataTotalScore.addColumn('number', 'Num Users');

            dataTotalScore.addRows([
<?php
$largestWonByCount = 0;
$count = 0;
for( $i = 1; $i <= $numAchievements; $i++ )
{
    if( $count++ > 0 )
        echo ", ";
    $wonByUserCount = $achDist[ $i ];

    if( $wonByUserCount > $largestWonByCount )
        $largestWonByCount = $wonByUserCount;

    echo "[ {v:$i, f:\"Earned $i achievement(s)\"}, $wonByUserCount ] ";
}

if( $largestWonByCount > 30 )
    $largestWonByCount = -2;
?>
            ]);

<?php
$numGridlines = $numAchievements;
?>

            var optionsTotalScore = {
                backgroundColor: 'transparent',
                //title: 'Achievement Distribution',
                titleTextStyle: {color: '#186DEE'}, //cc9900
                hAxis: {textStyle: {color: '#186DEE'}, gridlines: {count:<?php echo $numGridlines; ?>, color: '#334433'}, minorGridlines: {count: 0}, format: '#', slantedTextAngle: 90, maxAlternation: 0},
                vAxis: {textStyle: {color: '#186DEE'}, gridlines: {count:<?php echo $largestWonByCount + 1; ?>}, viewWindow: {min: 0}, format: '#'},
                legend: {position: 'none'},
                chartArea: {'width': '85%', 'height': '78%'},
                height: 260,
                colors: ['#cc9900'],
                pointSize: 4
            };

            function resize()
            {
                chartScoreProgress = new google.visualization.AreaChart(document.getElementById('chart_distribution'));
                chartScoreProgress.draw(dataTotalScore, optionsTotalScore);

                //google.visualization.events.addListener(chartScoreProgress, 'select', selectHandlerScoreProgress );
            }

            window.onload = resize();
            window.onresize = resize;
        }

    </script>

    <?php RenderSharedHeader( $user ); ?>
    <?php RenderFBMetaData( $pageTitle, "game", $gameData[ 'ImageIcon' ], "/Game/$gameID", "Game Info for $gameTitle ($consoleName)" ); ?>
    <?php RenderTitleTag( $pageTitle, $user ); ?>
    <?php RenderGoogleTracking(); ?>

    <script>

        var lastKnownAchRating = 0;
        var lastKnownGameRating = 0;

        function SetLitStars(container, numStars)
        {
            $(container + ' a').removeClass('starlit');
            $(container + ' a').removeClass('starhalf');

            if (numStars >= 0.5)
                $(container + ' a:first-child').addClass('starhalf');
            if (numStars >= 1.5)
                $(container + ' a:first-child + a').addClass('starhalf');
            if (numStars >= 2.5)
                $(container + ' a:first-child + a + a').addClass('starhalf');
            if (numStars >= 3.5)
                $(container + ' a:first-child + a + a + a').addClass('starhalf');
            if (numStars >= 4.5)
                $(container + ' a:first-child + a + a + a + a').addClass('starhalf');

            if (numStars >= 1)
            {
                $(container + ' a:first-child').removeClass('starhalf');
                $(container + ' a:first-child').addClass('starlit');
            }
            if (numStars >= 2)
            {
                $(container + ' a:first-child + a').removeClass('starhalf');
                $(container + ' a:first-child + a').addClass('starlit');
            }

            if (numStars >= 3)
            {
                $(container + ' a:first-child + a + a').removeClass('starhalf');
                $(container + ' a:first-child + a + a').addClass('starlit');
            }

            if (numStars >= 4)
            {
                $(container + ' a:first-child + a + a + a').removeClass('starhalf');
                $(container + ' a:first-child + a + a + a').addClass('starlit');
            }

            if (numStars >= 5)
            {
                $(container + ' a:first-child + a + a + a + a').removeClass('starhalf');
                $(container + ' a:first-child + a + a + a + a').addClass('starlit');
            }
        }

        function GetRating(gameID) {

            $('#ratinggame a').removeClass('starlit');
            $('#ratingach a').removeClass('starlit');

            $('.ratinggamelabel').html("Rating: ...");
            $('.ratingachlabel').html("Rating: ...");

            $.ajax({
                url: '/API/API_GetGameRating.php?i=' + gameID,
                dataType: 'json',
                success: function (results) {
                    results.GameID;
                    lastKnownGameRating = parseFloat(results.Ratings['Game']);
                    lastKnownAchRating = parseFloat(results.Ratings['Achievements']);
                    var gameRatingNumVotes = results.Ratings['GameNumVotes'];
                    var achRatingNumVotes = results.Ratings['AchievementsNumVotes'];

                    SetLitStars('#ratinggame', lastKnownGameRating);
                    SetLitStars('#ratingach', lastKnownAchRating);

                    $('.ratinggamelabel').html("Rating: " + lastKnownGameRating.toFixed(2) + " (" + gameRatingNumVotes + " votes)");
                    $('.ratingachlabel').html("Rating: " + lastKnownAchRating.toFixed(2) + " (" + achRatingNumVotes + " votes)");

                },
                error: function (temp, temp1, temp2) {
                    alert("Error " + temp + temp1 + temp2);
                }
            });
        }

        function SubmitRating(user, gameID, ratingObjectType, value)
        {
            $.ajax({
                url: '/API/API_SetGameRating.php?i=' + gameID + '&u=' + user + '&t=' + ratingObjectType + '&v=' + value,
                dataType: 'json',
                success: function (results) {
                    GetRating(<?php echo $gameID; ?>);
                },
                error: function (temp, temp1, temp2) {
                    alert("Error " + temp + temp1 + temp2);
                }
            });
        }

        //	Onload:
        $(function () {

            //	Add these handlers onload, they don't exist yet
            $('.starimg').hover(
                    function () {
                        //	On hover

                        if ($(this).parent().is($('#ratingach')))
                        {
                            //	Ach:
                            var numStars = 0;
                            if ($(this).hasClass('1star'))
                                numStars = 1;
                            else if ($(this).hasClass('2star'))
                                numStars = 2;
                            else if ($(this).hasClass('3star'))
                                numStars = 3;
                            else if ($(this).hasClass('4star'))
                                numStars = 4;
                            else if ($(this).hasClass('5star'))
                                numStars = 5;

                            SetLitStars('#ratingach', numStars);
                        } else
                        {
                            //	Game:
                            var numStars = 0;
                            if ($(this).hasClass('1star'))
                                numStars = 1;
                            else if ($(this).hasClass('2star'))
                                numStars = 2;
                            else if ($(this).hasClass('3star'))
                                numStars = 3;
                            else if ($(this).hasClass('4star'))
                                numStars = 4;
                            else if ($(this).hasClass('5star'))
                                numStars = 5;

                            SetLitStars('#ratinggame', numStars);
                        }
                    },
                    function () {
                        //	On leave
                        //GetRating( <?php echo $gameID; ?> );
                    });

            $('.rating').hover(
                    function () {
                        //	On hover
                    },
                    function () {
                        //	On leave
                        //GetRating( <?php echo $gameID; ?> );
                        if ($(this).is($('#ratingach')))
                            SetLitStars('#ratingach', lastKnownAchRating);
                        else
                            SetLitStars('#ratinggame', lastKnownGameRating);
                    });

            $('.starimg').click(function () {

                var numStars = 0;
                if ($(this).hasClass('1star'))
                    numStars = 1;
                else if ($(this).hasClass('2star'))
                    numStars = 2;
                else if ($(this).hasClass('3star'))
                    numStars = 3;
                else if ($(this).hasClass('4star'))
                    numStars = 4;
                else if ($(this).hasClass('5star'))
                    numStars = 5;

                var ratingType = 1;
                if ($(this).parent().is($('#ratingach')))
                    ratingType = 3;

                SubmitRating('<?php echo $user; ?>', <?php echo $gameID; ?>, ratingType, numStars);
            });

            GetRating(<?php echo $gameID; ?>);

        });

    </script>
</head>

<body>
    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>

    <div id="mainpage">
        <div id='leftcontainer'>

            <?php RenderErrorCodeWarning( 'left', $errorCode ); ?>
            <div id="achievement" class="left">
                <?php
                echo "<div class='navpath'>";
                echo "<a href='/gameList.php'>All Games</a>";
                echo " &raquo; <a href='/gameList.php?c=$consoleID'>$consoleName</a>";
                if( $flags == 5 )
                {
                    echo " &raquo; <a href='/Game/$gameID'>$gameTitle</a>";
                    echo " &raquo; <b>Unofficial Achievements</b>";
                }
                else
                    echo " &raquo; <b>$gameTitle</b>";
                echo "</div>";

                //	Dump all page info:
                $developer = $gameData[ 'Developer' ] != NULL ? $gameData[ 'Developer' ] : "Unknown";
                $publisher = $gameData[ 'Publisher' ] != NULL ? $gameData[ 'Publisher' ] : "Unknown";
                $genre = $gameData[ 'Genre' ] != NULL ? $gameData[ 'Genre' ] : "Unknown";
                $released = $gameData[ 'Released' ] != NULL ? $gameData[ 'Released' ] : "Unknown";
                $imageIcon = $gameData[ 'ImageIcon' ];
                $imageTitle = $gameData[ 'ImageTitle' ];
                $imageIngame = $gameData[ 'ImageIngame' ];

                echo "<table class='iconheader'><tbody>";
                echo "<tr>";
                echo "<td style='width:110px;' ><img src='$imageIcon' title='$pageTitle' width='96' height='96'/></td>";
                echo "<td><h3 class='longheader'>$pageTitle</h3>";
		        echo "<table class='gameinfo'><tbody>";
                echo "<tr>";
                echo "<td>Developer:</td>";
                echo "<td><b>$developer</b></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Publisher:</td>";
                echo "<td><b>$publisher</b></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Genre:</td>";
                echo "<td><b>$genre</b></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>First released:</td>";
                echo "<td><b>$released</b></td>";
                echo "</tr>";
                echo "</tbody></table>";
                echo "</tr>";
                echo "</tbody></table>";

                echo "<div class='gamescreenshots'>";
                echo "<table><tbody>";
                echo "<tr>";
                echo "<td>";
                echo "<img src='$imageTitle' width='$screenshotWidth' height='$screenshotHeight' />";
                echo "</td>";
                echo "<td>";
                echo "<img src='$imageIngame' width='$screenshotWidth' height='$screenshotHeight'/>";
                echo "</td>";
                echo "</tr>";
                echo "</tbody></table>";
                echo "</div>";

                echo "<div style='clear:both;'></div>";
                echo "</br>";

                if( isset( $user ) && $permissions >= \RA\Permissions::Developer )
                {
                    echo "<div class='devbox'>";
                    echo "<span onclick=\"$('#devboxcontent').toggle(500); return false;\">Dev (Click to show):</span><br/>";
                    echo "<div id='devboxcontent'>";
                    echo "<ul>";

                    if( $flags == 5 )
                        echo "<li><a href='/Game/$gameID'>View Core Achievements</a></li>";
                    else
                        echo "<li><a href='/gameInfo.php?ID=$gameID&f=5'>View Unofficial Achievements</a></li>";

                    echo "<li><a href='/achievementinspector.php?g=$gameID'>Manage Achievements</a></li>";
                    echo "<li><a href='/leaderboardList.php?g=$gameID'>Manage Leaderboards</a></li>";

                    echo "<li><a href='/attemptrename.php?g=$gameID'>Rename Game</a></li>";
                    echo "<li><a href='/attemptunlink.php?g=$gameID'>Unlink Game</a></li>";

                    if( $numLeaderboards == 0 )
                        echo "<li><a href='/requestcreatenewlb.php?u=$user&amp;c=$cookie&amp;g=$gameID'>Create First Leaderboard</a></li>";
                    echo "<li><a href='/request.php?r=recalctrueratio&amp;g=$gameID&amp;b=1'>Recalculate True Ratios</a></li>";
                    echo "<li><a href='/ticketmanager.php?g=$gameID&ampt=1'>View open tickets for this game</a></li>";
                    echo "<li><a href='/codenotes.php?g=$gameID'>Code Notes</a>";

                    echo "</br>";

                    echo "<li>Update title screenshot</li>";
                    echo "<form method='post' action='/uploadpic.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='i' value='$gameID' />";
                    echo "<input type='hidden' name='t' value='GAME_TITLE' />";
                    echo "<input type='file' name='file' id='file' />";
                    echo "<input type='submit' name='submit' style='float: right;' value='Submit' />";
                    echo "</form><br/>";

                    echo "<li>Update ingame screenshot</li>";
                    echo "<form method='post' action='/uploadpic.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='i' value='$gameID' />";
                    echo "<input type='hidden' name='t' value='GAME_INGAME' />";
                    echo "<input type='file' name='file' id='file' />";
                    echo "<input type='submit' name='submit' style='float: right;' value='Submit' />";
                    echo "</form><br/>";

                    echo "<li>Update game icon</li>";
                    echo "<form method='post' action='/uploadpic.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='i' value='$gameID' />";
                    echo "<input type='hidden' name='t' value='GAME_ICON' />";
                    echo "<input type='file' name='file' id='file' />";
                    echo "<input type='submit' name='submit' style='float: right;' value='Submit' />";
                    echo "</form><br/>";

                    echo "<li>Update game boxart</li>";
                    echo "<form method='post' action='/uploadpic.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='i' value='$gameID' />";
                    echo "<input type='hidden' name='t' value='GAME_BOXART' />";
                    echo "<input type='file' name='file' id='file' />";
                    echo "<input type='submit' name='submit' style='float: right;' value='Submit' />";
                    echo "</form><br/>";

                    echo "<li>Update game details:</br>";
                    echo "<form method='post' action='/submitgamedata.php' enctype='multipart/form-data'>";
                    echo "<table><tbody>";
                    echo "<input type='hidden' name='i' value='$gameID' />";
                    echo "<tr><td>Developer:</td><td style='width:100%'><input type='text' name='d' value='$developer' style='width:100%;'/></td></tr>";
                    echo "<tr><td>Publisher:</td><td style='width:100%'><input type='text' name='p' value='$publisher' style='width:100%;'/></td></tr>";
                    echo "<tr><td>Genre:</td><td style='width:100%'><input type='text' name='g' value='$genre' style='width:100%;'/></td></tr>";
                    echo "<tr><td>First Released:</td><td style='width:100%'><input type='text' name='r' value='$released' style='width:100%;'/></td></tr>";
                    echo "</tbody></table>";
                    echo "&nbsp;<input type='submit' style='float: right;' value='Submit' /></br></br>";
                    echo "<div style='clear:all;'></div>";
                    echo "</form>";
                    echo "</li>";

                    if( $permissions >= \RA\Permissions::Admin )
                    {
                        echo "<tr><td>";
                        echo "<form method='post' action='/submitgamedata.php' enctype='multipart/form-data'>";
                        echo "New Forum Topic ID:";
                        echo "<input type='hidden' name='i' value='$gameID' />";
                        echo "<input type='text' name='f' size='20'/>";
                        echo "<input type='submit' style='float: right;' value='Submit' size='37'/>";
                        echo "</form>";
                        echo "</td></tr>";
                    }

                    echo "<li>Similar Games</li>";
                    echo "<table><tbody>";
                    if( count( $gameAlts ) > 0 )
                    {
                        echo "<tr><td>";
                        echo "<form method='post' action='/submitgamedata.php' enctype='multipart/form-data'>";
                        echo "<input type='hidden' name='i' value='$gameID' />";

                        echo "To remove (game ID):";
                        echo "<select name='m'>";
                        echo "<option value='0' selected>-</option>";

                        foreach( $gameAlts as $gameAlt )
                        {
                            $gameAltID = $gameAlt[ 'gameIDAlt' ];
                            $gameAltTitle = $gameAlt[ 'Title' ];
                            $gameAltConsole = $gameAlt[ 'ConsoleName' ];
                            echo "<option value='$gameAltID'>$gameAltTitle ($gameAltConsole)</option>";
                        }
                        echo "</select>";
                        echo "<input type='submit' style='float: right;' value='Remove' size='37'/>";
                        echo "</form>";
                        echo "</td></tr>";
                    }

                    echo "<tr><td>";
                    echo "<form method='post' action='/submitgamedata.php' enctype='multipart/form-data'>";
                    echo "To add (game ID):";
                    echo "<input type='hidden' name='i' value='$gameID' />";
                    echo "<input type='text' name='n' class='searchboxgame' size='20'/>";
                    echo "<input type='submit' style='float: right;' value='Add' size='37'/>";
                    echo "</form>";
                    echo "</td></tr>";
                    echo "</tbody></table>";

                    echo "<li>Update <a href='https://docs.retroachievements.org/Rich-Presence/'>Rich Presence</a> script:</li>";
                    echo "<form method='post' action='/submitgamedata.php' enctype='multipart/form-data'>";
                    echo "<input type='hidden' value='$gameID' name='i'></input>";
                    echo "<textarea style='height:320px;' class='code fullwidth' name='x'>$richPresenceData</textarea></br>";
                    echo "<input type='submit' style='float: right;' value='Submit' size='37'/>";
                    echo "</form>";
                    echo "</li>";

                    echo "</ul>";

                    echo "</div>";

                    echo "</div>";
                }

                if( $flags == 5 )
                {
                    echo "<h4><b>Unofficial</b> Achievements</h4>";
                    echo "<a href='/Game/$gameID'><b>Click here to view the Core Achievements</b></a><br>";
                    echo "There are <b>$numAchievements Unofficial</b> achievements worth <b>$totalPossible</b> <span class='TrueRatio'>($totalPossibleTrueRatio)</span> points.<br/>";
                }
                else
                {
                    echo "<h4>Achievements</h4>";
                    echo "There are <b>$numAchievements</b> achievements worth <b>$totalPossible</b> <span class='TrueRatio'>($totalPossibleTrueRatio)</span> points.<br/>";
                }

                if( $numAchievements > 0 )
                {
                    echo "<b>Authors:</b> ";
                    foreach( $authors as $author )
                    {
                        echo "<a href='/User/$author'>$author</a>";
                        if( next($authors) != NULL) echo ', ';
                    }
                    echo "<br/>";
                    echo "<br/>";
                }

                if( isset( $user ) )
                {
                    $pctAwardedCasual = 0;
                    $pctAwardedHardcore = 0;
                    $pctComplete = 0;

                    if( $numAchievements )
                    {
                        $pctAwardedCasual = $numEarnedCasual / $numAchievements;
                        $pctAwardedHardcore = $numEarnedHardcore / $numAchievements;
                        $pctAwardedHardcoreProportion = 0;
                        if( $numEarnedHardcore > 0 )
                            $pctAwardedHardcoreProportion = $numEarnedHardcore / $numEarnedCasual;

                        $pctAwardedCasual = sprintf( "%01.0f", $pctAwardedCasual * 100.0 );
                        $pctAwardedHardcore = sprintf( "%01.0f", $pctAwardedHardcoreProportion * 100.0 );

                        $pctComplete = sprintf( "%01.0f", ( ( $numEarnedCasual + $numEarnedHardcore ) * 100.0 / $numAchievements ) );
                    }

                    echo "<div class='progressbar'>";
                    echo "<div class='completion' 			style='width:$pctAwardedCasual%'>";
                    echo "<div class='completionhardcore' 	style='width:$pctAwardedHardcore%'>";
                    echo "&nbsp;";
                    echo "</div>";
                    echo "</div>";
                    if( $pctComplete > 100.0 )
                        echo "<b>$pctComplete%</b> complete<br/>";
                    else
                        echo "$pctComplete% complete<br/>";
                    echo "</div>";
                }

                if( $user !== NULL && $numAchievements > 0 )
                {
                    echo "<a href='/User/$user'>$user</a> has won <b>$numEarnedCasual</b> achievements";
                    if( $totalEarnedCasual > 0 )
                        echo ", worth <b>$totalEarnedCasual</b> <span class='TrueRatio'>($totalEarnedTrueRatio)</span> points";
                    echo ".<br/>";
                    if( $numEarnedHardcore > 0 )
                    {
                        echo "<a href='/User/$user'>$user</a> has won <b>$numEarnedHardcore</b> HARDCORE achievements";
                        if( $totalEarnedHardcore > 0 )
                            echo ", worth a further <b>$totalEarnedHardcore</b> points";
                        echo ".<br/>";
                    }
                }

                if( $user !== NULL && $numAchievements > 0 )
                {
                    echo "<div style='float: right; clear: both;'>";

                    echo "<h4>Game Rating</h4>";

                    echo "<div class='rating' id='ratinggame'>";
                    echo "<a class='starimg starlit 1star'>1</a>";
                    echo "<a class='starimg starlit 2star'>2</a>";
                    echo "<a class='starimg starlit 3star'>3</a>";
                    echo "<a class='starimg starlit 4star'>4</a>";
                    echo "<a class='starimg starlit 5star'>5</a>";
                    echo "</div>";
                    echo "<span class='ratinggamelabel'>?</span>";

                    echo "</div>";
                    echo "</br>";
                }

                /* if( $user !== NULL && $numAchievements > 0 )
                  {
                  echo "<div style='float: right; clear: both;'>";

                  echo "<h4>Achievements Rating</h4>";

                  echo "<div class='rating' id='ratingach'>";
                  echo "<a class='starimg starlit 1star'>1</a>";
                  echo "<a class='starimg starlit 2star'>2</a>";
                  echo "<a class='starimg starlit 3star'>3</a>";
                  echo "<a class='starimg starlit 4star'>4</a>";
                  echo "<a class='starimg starlit 5star'>5</a>";
                  echo "</div>";
                  echo "<span class='ratingachlabel'>?</span>";

                  echo "</div>";
                  echo "</br>";
                  } */

                echo "<div style='clear: both;'>";
                echo "&nbsp;";
                echo "</div>";

                if( $numAchievements > 1 )
                {
                    echo "<div class='sortbyselector'><span>";
                    echo "Sort: ";

                    $sortType = ($sortBy < 10) ? "^" : "<sup>v</sup>";

                    $sort1 = ($sortBy == 1) ? 11 : 1;
                    $sort2 = ($sortBy == 2) ? 12 : 2;
                    $sort3 = ($sortBy == 3) ? 13 : 3;
                    $sort4 = ($sortBy == 4) ? 14 : 4;
                    $sort5 = ($sortBy == 5) ? 15 : 5;

                    $mark1 = ($sortBy % 10 == 1) ? "&nbsp;$sortType" : "";
                    $mark2 = ($sortBy % 10 == 2) ? "&nbsp;$sortType" : "";
                    $mark3 = ($sortBy % 10 == 3) ? "&nbsp;$sortType" : "";
                    $mark4 = ($sortBy % 10 == 4) ? "&nbsp;$sortType" : "";
                    $mark5 = ($sortBy % 10 == 5) ? "&nbsp;$sortType" : "";

                    echo "<a href='/Game/$gameID?s=$sort1'>Normal$mark1</a> - ";
                    echo "<a href='/Game/$gameID?s=$sort2'>Won By$mark2</a> - ";
                    // meleu: sorting by "date won" isn't implemented yet.
                    //if( isset( $user ) )
                    //    echo "<a href='/Game/$gameID?s=$sort3'>Date Won$mark3</a> - ";
                    echo "<a href='/Game/$gameID?s=$sort4'>Points$mark4</a> - ";
                    echo "<a href='/Game/$gameID?s=$sort5'>Title$mark5</a>";

                    echo "<sup>&nbsp;</sup></span></div>";
                }

                echo "<table class='achievementlist'><tbody>";

                if( isset( $achievementData ) )
                {
                    for( $i = 0; $i < 2; $i++ )
                    {
                        if( $i == 0 && $numEarnedCasual == 0 )
                            continue; //	remove potential unnecessary empty table

                        $numOutput = 0;

                        foreach( $achievementData as &$nextAch )
                        {
                            //print_r( $nextAch );

                            $achieved = (isset( $nextAch[ 'DateEarned' ] ));

                            if( $i == 0 && $achieved == FALSE )
                                continue;
                            else if( $i == 1 && $achieved == TRUE )
                                continue;

                            $achID = $nextAch[ 'ID' ];
                            $achTitle = $nextAch[ 'Title' ];
                            $achDesc = $nextAch[ 'Description' ];
                            $achPoints = $nextAch[ 'Points' ];
                            $achTrueRatio = $nextAch[ 'TrueRatio' ];
                            $dateAch = "";
                            if( $achieved )
                                $dateAch = $nextAch[ 'DateEarned' ];
                            $achBadgeName = $nextAch[ 'BadgeName' ];

                            $earnedOnHardcore = isset( $nextAch[ 'DateEarnedHardcore' ] );

                            $achDesc = str_replace( '"', '\'', $achDesc );

                            $imgClass = $earnedOnHardcore ? 'goldimagebig' : 'badgeimg';
                            $tooltipText = $earnedOnHardcore ? '<br clear=all>Unlocked: ' . getNiceDate( strtotime( $nextAch[ 'DateEarnedHardcore' ] ) ) . '</br>-=HARDCORE=-' : '';

                            $wonBy = $nextAch[ 'NumAwarded' ];
                            $completionPctCasual = sprintf( "%01.2f", ($wonBy / $numDistinctPlayersCasual) * 100 );
                            $wonByHardcore = $nextAch[ 'NumAwardedHardcore' ];
                            $completionPctHardcore = sprintf( "%01.2f", ($wonByHardcore / $numDistinctPlayersCasual) * 100 );

                            if( $user == "" || !$achieved )
                                $achBadgeName .= "_lock";

                            echo "<tr>";
                            echo "<td>";

                            echo "<div class='achievemententry'>";

                            echo "<div class='achievemententryicon'>";
                            echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, TRUE, TRUE, $tooltipText, 64, $imgClass );
                            echo "</div>";

                            $pctAwardedCasual = 0;
                            $pctAwardedHardcore = 0;
                            $pctComplete = 0;

                            if( $numDistinctPlayersCasual )
                            {
                                $pctAwardedCasual = $wonBy / $numDistinctPlayersCasual;
                                $pctAwardedHardcore = $wonByHardcore / $numDistinctPlayersCasual;
                                $pctAwardedHardcoreProportion = 0;
                                if( $wonByHardcore > 0 && $wonBy > 0 )
                                    $pctAwardedHardcoreProportion = $wonByHardcore / $wonBy;

                                $pctAwardedCasual = sprintf( "%01.2f", $pctAwardedCasual * 100.0 );
                                $pctAwardedHardcore = sprintf( "%01.2f", $pctAwardedHardcoreProportion * 100.0 );

                                $pctComplete = sprintf( "%01.2f", ( ( $wonBy + $wonByHardcore ) * 100.0 / $numDistinctPlayersCasual ) );
                            }

                            echo "<div class='progressbar allusers'>";
                            echo "<div class='completion allusers' 			style='width:$pctAwardedCasual%'>";
                            echo "<div class='completionhardcore allusers' 	style='width:$pctAwardedHardcore%'>";
                            echo "&nbsp;";
                            echo "</div>";
                            echo "</div>";
                            if( $wonByHardcore > 0 )
                                echo "won by $wonBy <strong alt='HARDCORE'>($wonByHardcore)</strong> of $numDistinctPlayersCasual ($pctAwardedCasual%)<br/>";
                            else
                                echo "won by $wonBy of $numDistinctPlayersCasual ($pctAwardedCasual%)<br/>";
                            echo "</div>"; //progressbar

                            echo "<div class='achievementdata'>";
                            echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, FALSE, FALSE, "", 64, $imgClass );
                            echo " <span class='TrueRatio'>($achTrueRatio)</span>";
                            echo "<br/>";
                            echo "$achDesc<br/>";
                            echo "</div>";

                            if( $achieved )
                                echo "<div class='date smalltext'>unlocked on<br/>$dateAch<br/></div>";


                            echo "</div>"; //	achievemententry
                            echo "</td>";

                            echo "</tr>";
                            $numOutput += 1;
                        }
                    }
                }
                echo "</tbody></table>";

                echo "<b>Forum Topic: </b>";
                RenderLinkToGameForum( $user, $cookie, $gameTitle, $gameID, $forumTopicID, $permissions );
                echo "<br><br>";

                //	Render article comments
                $forceAllowDeleteComments = $permissions >= \RA\Permissions::Admin;
                RenderCommentsComponent( $user, $numArticleComments, $commentData, $gameID, 1, $forceAllowDeleteComments );
                ?>
            </div>
        </div>

        <div id='rightcontainer'>
            <?php
            //	Render game box art
            RenderBoxArt( $gameData[ 'ImageBoxArt' ] );

            if( isset( $user ) )
            {
                echo "<h3>More Info</h3>";
                echo "<b>About \"$gameTitle ($consoleName)\":</b><br>";
                echo "<ul>";
                echo "<li>- ";
                RenderLinkToGameForum( $user, $cookie, $gameTitle, $gameID, $forumTopicID, $permissions );
                echo "</li>";
                echo "<li>- <a href='/linkedhashes.php?g=$gameID'>Hashes linked to this game</a></li>";
                echo "<li>- <a href='/ticketmanager.php?g=$gameID&ampt=1'>Open Tickets for this game</a></li>";
                //if( $flags == 5 )
                    //echo "<li>- <a href='/Game/$gameID'>View Core Achievements</a></li>";
                //else
                    //echo "<li>- <a href='/gameInfo.php?ID=$gameID&f=5'>View Unofficial Achievements</a></li>";
                echo "</ul><br>";
            }

            if( count( $gameAlts ) > 0 )
                RenderGameAlts( $gameAlts );

            if( $user == NULL )
                RenderTutorialComponent();

            RenderGameCompare( $user, $gameID, $friendScores, $totalPossible );

            echo "<div id='achdistribution' class='component' >";
            echo "<h3>Achievement Distribution</h3>";
            echo "<div id='chart_distribution'></div>";
            echo "</div>";

            RenderTopAchieversComponent( $gameTopAchievers );
            RenderGameLeaderboardsComponent( $gameID, $lbData );
            ?>

        </div>
    </div>

    <?php RenderFooter(); ?>

</body>
</html>
