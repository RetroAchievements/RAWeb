<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

$userPage = seekGET( 'u', $user );

if( !isset( $userPage ) || !isValidUsername( $userPage ) )
{
    header( "Location: " . getenv('APP_URL') ."?e=notloggedin" );
    exit;
}

$listOffset = seekGET( 'o', 0 );
$sortBy = seekGET( 's', 3 );
$maxDays = seekGET( 'c', 15 );
$userBestDaysList = getUserBestDaysList( $userPage, $listOffset, $maxDays, $sortBy );

$sortByGraphName = 'Total Points';
if( $sortBy == 2 || $sortBy == 12 )
    $sortByGraphName = 'Num Achievements Earned';

$errorCode = seekGET( 'e' );
$pageTitle = "$userPage's Legacy";

$userPagePoints = getScore( $userPage );

getUserActivityRange( $userPage, $userSignedUp, $unused );

$userAwards = getUsersSiteAwards( $userPage );
$userCompletedGamesList = getUsersCompletedGamesAndMax( $userPage );

$userCompletedGames = [];

//	Merge all elements of $userCompletedGamesList into one unique list
for ($i = 0; $i < count($userCompletedGamesList); $i++) {
    $gameID = $userCompletedGamesList[$i]['GameID'];

    if ($userCompletedGamesList[$i]['HardcoreMode'] == 0) {
        $userCompletedGames[$gameID] = $userCompletedGamesList[$i];
    }

    $userCompletedGames[$gameID]['NumAwardedHC'] = 0; //	Update this later, but fill in for now
}

for ($i = 0; $i < count($userCompletedGamesList); $i++) {
    $gameID = $userCompletedGamesList[$i]['GameID'];
    if ($userCompletedGamesList[$i]['HardcoreMode'] == 1) {
        $userCompletedGames[$gameID]['NumAwardedHC'] = $userCompletedGamesList[$i]['NumAwarded'];
    }
}

function scorePctCompare($a, $b)
{
    return $a['PctWon'] < $b['PctWon'];
}

usort($userCompletedGames, "scorePctCompare");

$userCompletedGamesList = $userCompletedGames;

$userAwardsGames = Array();
$userAwardsOther = Array();
for( $i = 0; $i < count( $userAwards ); $i++ )
{
    if( $userAwards[ $i ][ 'AwardType' ] == 1 )
        $userAwardsGames[ $userAwards[ $i ][ 'AwardData' ] ] = $userAwards[ $i ]; //	squashes 'mastered' into 'completed'
    else
        $userAwardsOther[] = $userAwards[ $i ];
}

$userAwards = Array();
foreach( $userAwardsGames as $nextUserAward )
    $userAwards[] = $nextUserAward;
foreach( $userAwardsOther as $nextUserAward )
    $userAwards[] = $nextUserAward;


//	the past week
$userScoreData = getAwardedList( $userPage, 0, 1000 );

//var_dump( $userScoreData );

RenderDocType( TRUE );
?>

<head>
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
            dataTotalScore.addColumn('date', 'Date Earned');
            dataTotalScore.addColumn('number', 'Total Score');

            dataTotalScore.addRows([
<?php
$count = 0;
foreach( $userScoreData as $dayInfo )
{
    if( $count++ > 0 )
        echo ", ";

    $nextDay = $dayInfo[ 'Day' ];
    $nextMonth = $dayInfo[ 'Month' ];
    $nextYear = $dayInfo[ 'Year' ];
    $nextDate = $dayInfo[ 'Date' ];

    $dateStr = getNiceDate( strtotime( $nextDate ), TRUE );
    $value = $dayInfo[ 'CumulScore' ];

    //echo "[\"$dateStr\", $value]";
    //echo "[ {v:new Date(2013, 1, 1), f:\"$dateStr\"}, $value]";
    echo "[ {v:new Date($nextYear,$nextMonth,$nextDay), f:'$dateStr'}, $value ]";
    //echo "[ new Date( Date.parse( '$nextDate' ) ), $value]";
}
?>
            ]);


            var optionsTotalScore = {
                backgroundColor: 'transparent',
                title: '<?php echo $sortByGraphName; ?>',
                titleTextStyle: {color: '#186DEE'}, //cc9900
                hAxis: {textStyle: {color: '#186DEE'}, slantedTextAngle: 90},
                vAxis: {textStyle: {color: '#186DEE'}},
                legend: {position: 'none'},
                chartArea: {'width': '86%', 'height': '70%'},
                height: 250,
                colors: ['#cc9900']
            };


            var dataBestDays = new google.visualization.DataTable();

            // Declare columns
            dataBestDays.addColumn('string', 'Date');	//	NOT date! this is non-continuous data
            dataBestDays.addColumn('number', 'Points Earned');

            dataBestDays.addRows([
<?php
$arrayToUse = $userBestDaysList;
if( $sortBy == 1 || $sortBy > 11 )
    $arrayToUse = array_reverse( $userBestDaysList );

$count = 0;
foreach( $arrayToUse as $dayInfo )
{
    if( $count++ > 0 )
        echo ", ";

    $nextDay = $dayInfo[ 'Day' ];
    $nextMonth = $dayInfo[ 'Month' ];
    $nextYear = $dayInfo[ 'Year' ];

    $dateStr = "$nextDay/$nextMonth";
    if( $nextYear != date( 'Y' ) )
        $dateStr = "$nextDay/$nextMonth/$nextYear";

    $nextNumAwarded = $dayInfo[ 'NumAwarded' ];
    $nextTotalPointsEarned = $dayInfo[ 'TotalPointsEarned' ];

    $value = $nextTotalPointsEarned;
    if( $sortBy == 2 || $sortBy == 12 )
        $value = $nextNumAwarded;

    //echo "[ {v:new Date($nextYear,$nextMonth,$nextDay), f:'$dateStr'}, $value ]";
    echo "[ '$dateStr', $value ]";
}
?>
            ]);


            var optionsBestDays = {
                backgroundColor: 'transparent',
                title: '<?php echo $sortByGraphName; ?>',
                titleTextStyle: {color: '#186DEE'},
                hAxis: {textStyle: {color: '#186DEE'}, slantedTextAngle: 90},
                vAxis: {textStyle: {color: '#186DEE'}},
                legend: {position: 'none'},
                chartArea: {'width': '90%', 'height': '70%'},
                showRowNumber: false,
                view: {columns: [0, 1]},
                height: 250,
                colors: ['#cc9900']
            };


            var chartBestDays;
            var chartScoreProgress;

            function selectHandlerBestDays(e)
            {
                if (chartBestDays.getSelection().length >= 1)
                {
                    var dateAbbr = dataBestDays.getFormattedValue(chartBestDays.getSelection()[0].row, 0);
                    var firstSlashAt = dateAbbr.indexOf("/");
                    var secondSlashAt = dateAbbr.lastIndexOf("/");

                    var d = new Date;

                    var day = dateAbbr.split('/')[0];
                    var month = dateAbbr.split('/')[1];

                    if (firstSlashAt != secondSlashAt)
                    {
                        d.setFullYear(dateAbbr.split('/')[2], month - 1, day);
                    } else
                    {
                        d.setFullYear(new Date().getFullYear(), month - 1, day);
                    }

                    //alert( day + " " + month + " " + d.getTime() );

                    window.location = '/historyexamine.php?d=' + parseInt(d.getTime() / 1000) + '&u=' + <?php echo "'$userPage'"; ?>;
                }
            }

            function selectHandlerScoreProgress(e)
            {
                if (chartScoreProgress.getSelection().length >= 1)
                {
                    var dateFormatted = dataTotalScore.getFormattedValue(chartScoreProgress.getSelection()[0].row, 0);

                    var d = new Date(Date.parse(dateFormatted));
                    var dAdj = new Date(d.getTime() + 60000 * 60 * 12);	//	Adjusted by 60000 (min) times 60 (hour) times 12 (middle of day)

                    var nUnix = parseInt(dAdj.getTime() / 1000);

                    window.location = '/historyexamine.php?d=' + nUnix + '&u=' + <?php echo "'$userPage'"; ?>;
                }
            }

            function resize()
            {
                chartBestDays = new google.visualization.ColumnChart(document.getElementById('chart_bestdays'));
                chartBestDays.draw(dataBestDays, optionsBestDays);

                chartScoreProgress = new google.visualization.AreaChart(document.getElementById('chart_scoreprogress'));
                chartScoreProgress.draw(dataTotalScore, optionsTotalScore);

                google.visualization.events.addListener(chartBestDays, 'select', selectHandlerBestDays);
                google.visualization.events.addListener(chartScoreProgress, 'select', selectHandlerScoreProgress);
            }

            window.onload = resize();
            window.onresize = resize;
        }
    </script>

<?php RenderSharedHeader( $user ); ?>
<?php RenderTitleTag( $pageTitle, $user ); ?>
<?php RenderGoogleTracking(); ?>
</head>

<body>
    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
<?php RenderToolbar( $user, $permissions ); ?>

    <div id='mainpage'>
        <div id='leftcontainer'>
    <?php
    echo "<div class='left'>";

    echo "<div class='navpath'>";
    echo "<a href='/userList.php'>All Users</a>";
    echo " &raquo; <a href='/User/$userPage'>$userPage</a>";
    echo " &raquo; <b>History</b>";
    echo "</div>";

    echo "<h3>$userPage's legacy</h3>";

    echo "<div class='userlegacy'>";
    echo "<img src='/UserPic/$userPage.png' alt='$userPage' align='right' width='64' height='64'>";
    echo "<b><a href='/User/$userPage'><strong>$userPage</strong></a> ($userPagePoints points)</b><br/>";

    echo "Member since: " . getNiceDate( strtotime( $userSignedUp ), TRUE ) . "<br/>";
    echo "<br/>";
    echo "<br/>";
    echo "<br/>";

    echo "</div>";

    echo "<div id='chart_scoreprogress'></div>";

    echo "<h3>Best Days</h3>";
    echo "<div id='chart_bestdays'></div>";


    echo "<table class='smalltable'><tbody>";

    $sort1 = ($sortBy == 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;

    echo "<tr>";
    echo "<th><a href='/history.php?s=$sort1'>Date</a></th>";
    echo "<th><a href='/history.php?s=$sort2'>Num Achievements</a></th>";
    echo "<th><a href='/history.php?s=$sort3'>Score Earned</a></th>";
    echo "</tr>";

    $dayCount = 0;
    foreach( $userBestDaysList as $dayInfo )
    {
        $nextDay = $dayInfo[ 'Day' ];
        $nextMonth = $dayInfo[ 'Month' ];
        $nextYear = $dayInfo[ 'Year' ];
        $nextNumAwarded = $dayInfo[ 'NumAwarded' ];
        $nextTotalPointsEarned = $dayInfo[ 'TotalPointsEarned' ];

        $dateUnix = strtotime( "$nextDay-$nextMonth-$nextYear" );
        $dateStr = getNiceDate( $dateUnix, TRUE );

        if( $dayCount++ % 2 == 0 )
            echo "<tr>";
        else
            echo "<tr class='alt'>";

        echo "<td>$dateStr</td>";
        echo "<td><a href='historyexamine.php?d=$dateUnix&u=$userPage'>$nextNumAwarded</a></td>";
        echo "<td><a href='historyexamine.php?d=$dateUnix&u=$userPage'>$nextTotalPointsEarned</a></td>";

        echo "</tr>";
    }

    echo "</tbody></table>";

    echo "</div>";
    ?>
        </div>
        <div id='rightcontainer'>
            <?php
            if( $user !== NULL )
                RenderScoreLeaderboardComponent( $user, $points, TRUE );

            RenderSiteAwards( $userAwards );
            RenderCompletedGamesList( $user, $userCompletedGamesList );
            ?>
        </div>

    </div>

            <?php RenderFooter(); ?>

</body>
</html>

