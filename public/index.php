<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );
	
$playersOnlineArray = array();

if (file_exists("../storage/logs/playersonline.log")) {
	$playersOnlineCSV = file_get_contents("../storage/logs/playersonline.log");

	$playersCSV = preg_split('/\n|\r\n?/', $playersOnlineCSV);

	for ($i = 0; $i < 48; $i++) {
	    if(isset($playersCSV[count($playersCSV) - ($i + 2)])) {
		    $playersOnlineArray[] = $playersCSV[count($playersCSV) - ($i + 2)];
        }
	}
}
$staticData = getStaticData();
$errorCode = seekGET( 'e' );
$mobileBrowser = IsMobileBrowser();

RA_SetCookie( "RA_MobileActive", "$mobileBrowser" );
if( $mobileBrowser )
{
    //if( !RA_CookieExists( 'RAPrefs_CSS' ) )
    //	RA_SetCookie( 'RAPrefs_CSS', '/css/rac_mobile.css' );
    //LoadCSS( '/css/_mobile.css' );
}

$mostPopularCount = seekGET( 'p', 10 );

RenderDocType();
?>

<head>
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
            dataTotalScore.addColumn('datetime', 'Time');
            dataTotalScore.addColumn('number', 'Players Online');

            dataTotalScore.addRows([
<?php
$largestWonByCount = 0;
$count = 0;
$now = date( "Y/m/d G:0:0" );
//error_log( $now );

for( $i = 0; $i < 48; $i++ )
{
	if( count($playersOnlineArray) < $i )
		continue;
	
    $players = empty($playersOnlineArray[ $i ]) ? 0 : $playersOnlineArray[ $i ];
    settype( $players, 'integer' );

    if( $i != 0 )
        echo ", ";

    $mins = $i * 30;

    $timestamp = strtotime( "-$mins minutes", strtotime( $now ) );

    $yr = date( "Y", $timestamp );
    $month = date( "m", $timestamp ) - 1; //	PHP-js datetime
    $day = date( "d", $timestamp );
    $hour = date( "G", $timestamp );
    $min = date( "i", $timestamp );

    echo "[ new Date($yr,$month,$day,$hour,$min), {v:$players, f:\"$players online\"} ] ";
}
?>
            ]);

<?php
$numGridlines = 24;
?>

            var optionsTotalScore = {
                backgroundColor: 'transparent',
                //title: 'Achievement Distribution',
                titleTextStyle: {color: '#186DEE'}, //cc9900
                //hAxis: {textStyle: {color: '#186DEE'}, gridlines:{count:24, color: '#334433'}, minorGridlines:{count:0}, format:'#', slantedTextAngle:90, maxAlternation:0 },
                hAxis: {textStyle: {color: '#186DEE'}},
                vAxis: {textStyle: {color: '#186DEE'}, viewWindow: {min: 0}, format: '#'},
                legend: {position: 'none'},
                chartArea: {'width': '85%', 'height': '78%'},
                height: 160,
                colors: ['#cc9900'],
                pointSize: 4
            };

            function resize()
            {
                chartScoreProgress = new google.visualization.AreaChart(document.getElementById('chart_usersonline'));
                chartScoreProgress.draw(dataTotalScore, optionsTotalScore);

                //google.visualization.events.addListener(chartScoreProgress, 'select', selectHandlerScoreProgress );
            }

            function onload()
            {
                resize();

            }

            window.onload = onload();
            window.onresize = resize;
        }

    </script>

    <?php
    RenderSharedHeader( $user );
    RenderTitleTag( NULL, $user );
    RenderGoogleTracking();
    ?>
    <link type='text/css' rel='stylesheet' href='/rcarousel/widget/css/rcarousel.css' />
    <link type='text/css' rel='stylesheet' href='/rcarousel/rcarousel-ra.css' />

</head>

<body>

    <script type='text/javascript' src="js/ping_chat.js"></script>
<!--    <script type='text/javascript' src="js/ping_feed.js"></script>-->

    <script type="text/javascript" src="/rcarousel/widget/lib/jquery.ui.widget.min.js"></script>
    <script type="text/javascript" src="/rcarousel/widget/lib/jquery.ui.rcarousel.js"></script>

    <script type="text/javascript">
        //<![CDATA[
        $(function () {
            function generatePages() {
                var _total, i, _link;

                _total = $("#carousel").rcarousel("getTotalPages");

                for (i = 0; i < _total; i++) {
                    _link = $("<a href='#'></a>");

                    $(_link)
                            .bind("click", {page: i},
                                    function (event) {
                                        $("#carousel").rcarousel("goToPage", event.data.page);
                                        event.preventDefault();
                                    }
                            )
                            .addClass("bullet off")
                            .appendTo("#carouselpages");
                }

                // mark first page as active
                $("a:eq(0)", "#carouselpages")
                        .removeClass("off")
                        .addClass("on")
                        .css("background-image", "url(<?php echo getenv('APP_STATIC_URL') ?>/Images/page-on.png)");

                $('.newstitle').css('opacity', 0.0).delay(500).fadeTo("slow", 1.0);
                $('.newstext').css('opacity', 0.0).delay(900).fadeTo("slow", 1.0);
                $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo("slow", 1.0);
            }

            function pageLoaded(event, data) {
                $("a.on", "#carouselpages")
                        .removeClass("on")
                        .css("background-image", "url(<?php echo getenv('APP_STATIC_URL') ?>/Images/page-off.png)");

                $("a", "#carouselpages")
                        .eq(data.page)
                        .addClass("on")
                        .css("background-image", "url(<?php echo getenv('APP_STATIC_URL') ?>/Images/page-on.png)");
            }

            function onNext() {
                $('.newstitle').css('opacity', 0.0).delay(500).fadeTo("slow", 1.0);
                $('.newstext').css('opacity', 0.0).delay(900).fadeTo("slow", 1.0);
                $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo("slow", 1.0);
            }

            function onPrev() {
                //alert( "onPrev" );
            }

            $("#carousel").rcarousel(
                    {
                        visible: 1,
                        step: 1,
                        speed: 500,
                        auto: {
                            enabled: true,
                            interval: 7000
                        },
                        width: <?php echo $mobileBrowser ? "220" : "480"; ?>,
                        height: 220,
                        start: generatePages,
                        pageLoaded: pageLoaded,
                        onNext: onNext,
                        onPrev: onPrev
                    }
            );

            $("#ui-carousel-next")
                    .add("#ui-carousel-prev")
                    .add(".bullet")
                    .hover(
                            function () {
                                $(this).css("opacity", 0.7);
                            },
                            function () {
                                $(this).css("opacity", 1.0);
                            }
                    )
                    .click(
                            function () {
                                //alert( "Handler for .click() called." );
                                //$( 'body' ).find( '.newstext' ).fadeTo( 0, 0 );
                                $('.newstitle').css('opacity', 0.0).delay(500).fadeTo("slow", 1.0);
                                $('.newstext').css('opacity', 0.0).delay(900).fadeTo("slow", 1.0);
                                $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo("slow", 1.0);
                                $('.wrapper').pixastic("desaturate");
                            }
                    );

            refreshActivePlayers();
            setInterval(refreshActivePlayers, 2000 * 60);

<?php
if( $mobileBrowser == FALSE )
{
    // echo "init_chat(50); init_feed();";
    echo "init_chat(50);";
}
else
{
    echo "init_chat(50);";
}
?>
        });

        //]]>
    </script>
    <script type="text/javascript" src="js/jquery.githubRepoWidget.js"></script>

    <?php
    RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions );
    RenderToolbar( $user, $permissions );
    ?>
    <div id='mainpage'>
        <div id='leftcontainer'>
            <?php
            RenderErrorCodeWarning( 'left', $errorCode );
            if (!isset($user)) {
                RenderWelcomeComponent();
            }
            RenderNewsComponent();
            //RenderFeedComponent( $user );
            //RenderDemoVideosComponent();
            RenderRecentlyUploadedComponent( 5 );
            RenderActivePlayersComponent();
            RenderCurrentlyOnlineComponent( NULL );
            echo "<div style='min-height: 160px;' id='chart_usersonline'></div>";
            RenderRecentForumPostsComponent( 4 );
            ?>
        </div>
        <div id='rightcontainer'>
            <?php
            echo '<div class=\'patron\' style="margin-top: 10px"><a href=\'https://www.patreon.com/bePatron?u=5403777\' target="_blank" rel="noopener">Become a Patron!</a><!--script async src="https://c6.patreon.com/becomePatronButton.bundle.js"></script--></div>';
            echo '<div class=\'discord\' style="margin-bottom: 10px"><a href=\'https://discord.gg/'.getenv('DISCORD_INVITE_ID').'\' target="_blank" rel="noopener"><img alt="Join us on Discord!" style="width:172px; display: block; margin: 0 auto;" src="https://s3.amazonaws.com/uploads.uservoice.com/logo/design_setting/294519/original/Discord-Logo_Wordmark-White.png?1445475649"></a></div>';
            RenderDocsComponent();
            RenderAOTWComponent( $staticData[ 'Event_AOTW_AchievementID' ], $staticData[ 'Event_AOTW_ForumID' ] );
            //RenderTwitchTVStream();
            RenderChat( $user, 320, '', true );
            if( $user !== NULL )
            {
                RenderScoreLeaderboardComponent( $user, $points, TRUE, 5 );
            }
            //RenderMostPopularTitles( 7, 0, $mostPopularCount );
            RenderScoreLeaderboardComponent( $user, $points, FALSE, 5 );
            RenderStaticDataComponent( $staticData );
            //RenderTwitterFeed();
            //echo "<h3>Development Progress</h3>";
            //echo "<div class='github-widget' data-repo='RetroAchievements/RASuite'></div>";
            // if( $mobileBrowser ) {
            //     RenderDemoVideosComponent();
            // }
            ?>
        </div>
    </div>
    <?php RenderFooter(); ?>
</body>
</html>
