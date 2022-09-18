<?php

authenticateFromCookie($user, $permissions, $userDetails);

$playersOnlineArray = [];

if (file_exists("../storage/logs/playersonline.log")) {
    $playersOnlineCSV = file_get_contents("../storage/logs/playersonline.log");

    $playersCsv = preg_split('/\n|\r\n?/', $playersOnlineCSV);
    $playersCsvCount = is_countable($playersCsv) ? count($playersCsv) : 0;

    for ($i = 0; $i < 48; $i++) {
        if (isset($playersCsv[$playersCsvCount - ($i + 2)])) {
            $playersOnlineArray[] = $playersCsv[count($playersCsv) - ($i + 2)];
        }
    }
}
$staticData = getStaticData();

$mostPopularCount = requestInputSanitized('p', 10, 'integer');

RenderContentStart();
?>
<link type="text/css" rel="stylesheet" href="<?= asset('/vendor/rcarousel/rcarousel.css') ?>"/>
<script src="<?= asset('/vendor/rcarousel/jquery.ui.widget.min.js') ?>"></script>
<script src="<?= asset('/vendor/rcarousel/jquery.ui.rcarousel.min.js') ?>"></script>
<script src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.load('visualization', '1.0', { 'packages': ['corechart'] });
    google.setOnLoadCallback(drawCharts);

    function drawCharts() {
        var dataTotalScore = new google.visualization.DataTable();

        // Declare columns
        dataTotalScore.addColumn('datetime', 'Time');
        dataTotalScore.addColumn('number', 'Players Online');

        dataTotalScore.addRows([
            <?php
            $largestWonByCount = 0;
            $count = 0;
            $now = date("Y/m/d G:0:0");
            for ($i = 0; $i < 48; $i++) {
                if (count($playersOnlineArray) < $i) {
                    continue;
                }
                $players = empty($playersOnlineArray[$i]) ? 0 : $playersOnlineArray[$i];
                settype($players, 'integer');

                if ($i != 0) {
                    echo ", ";
                }
                $mins = $i * 30;

                $timestamp = strtotime("-$mins minutes", strtotime($now));

                $yr = date("Y", $timestamp);
                $month = date("m", $timestamp) - 1; // PHP-js datetime
                $day = date("d", $timestamp);
                $hour = date("G", $timestamp);
                $min = date("i", $timestamp);

                echo "[ new Date($yr,$month,$day,$hour,$min), {v:$players, f:\"$players online\"} ] ";
            }
            ?>
        ]);

        <?php
        $numGridlines = 24;
        ?>

        var optionsTotalScore = {
            backgroundColor: 'transparent',
            titleTextStyle: { color: '#186DEE' }, // cc9900
            hAxis: { textStyle: { color: '#186DEE' } },
            vAxis: { textStyle: { color: '#186DEE' }, viewWindow: { min: 0 }, format: '#' },
            legend: { position: 'none' },
            chartArea: { 'width': '85%', 'height': '78%' },
            height: 160,
            colors: ['#cc9900'],
            pointSize: 4,
        };

        function resize() {
            chartScoreProgress = new google.visualization.AreaChart(document.getElementById('chart_usersonline'));
            chartScoreProgress.draw(dataTotalScore, optionsTotalScore);
            //google.visualization.events.addListener(chartScoreProgress, 'select', selectHandlerScoreProgress );
        }

        window.onload = resize();
        window.onresize = resize;
    }
</script>

<script>
    $(function () {
        function generatePages() {
            var _total, i, _link;

            _total = $('#carousel').rcarousel('getTotalPages');

            for (i = 0; i < _total; i++) {
                _link = $('<a href=\'#\'></a>');

                $(_link).bind('click', { page: i },
                    function (event) {
                        $('#carousel').rcarousel('goToPage', event.data.page);
                        event.preventDefault();
                    },
                ).addClass('bullet').appendTo('#carouselpages');
            }

            // mark first page as active
            $('a:eq(0)', '#carouselpages').addClass('on');

            $('.newstitle').css('opacity', 0.0).delay(500).fadeTo('slow', 1.0);
            $('.newstext').css('opacity', 0.0).delay(900).fadeTo('slow', 1.0);
            $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo('slow', 1.0);
        }

        function pageLoaded(event, data) {
            $('a.on', '#carouselpages').removeClass('on');

            $('a', '#carouselpages').eq(data.page).addClass('on');
        }

        function onNext() {
            $('.newstitle').css('opacity', 0.0).delay(500).fadeTo('slow', 1.0);
            $('.newstext').css('opacity', 0.0).delay(900).fadeTo('slow', 1.0);
            $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo('slow', 1.0);
        }

        function onPrev() {
        }

        $('#carousel').rcarousel(
            {
                visible: 1,
                step: 1,
                speed: 500,
                auto: {
                    enabled: true,
                    interval: 7000,
                },
                width: 480,
                height: 220,
                start: generatePages,
                pageLoaded: pageLoaded,
                onNext: onNext,
                onPrev: onPrev,
            },
        );

        $('#ui-carousel-next').add('#ui-carousel-prev').add('.bullet').hover(
            function () {
                $(this).css('opacity', 0.7);
            },
            function () {
                $(this).css('opacity', 1.0);
            },
        ).click(
            function () {
                $('.newstitle').css('opacity', 0.0).delay(500).fadeTo('slow', 1.0);
                $('.newstext').css('opacity', 0.0).delay(900).fadeTo('slow', 1.0);
                $('.newsauthor').css('opacity', 0.0).delay(1100).fadeTo('slow', 1.0);
            },
        );
    });
</script>
<div id='mainpage'>
    <div id="leftcontainer">
        <?php
        if (empty($user)) {
            echo view('content.welcome')->render();
        }
        RenderNewsComponent();
        renderFinishedClaimsComponent(5);
        RenderActivePlayersComponent();
        RenderCurrentlyOnlineComponent();
        renderNewClaimsComponent(5);
        RenderRecentForumPostsComponent($permissions, 4);
        ?>
    </div>
    <div id="rightcontainer">
        <?php
        echo '<div class=\'text-center\' style="margin-bottom: 10px"><a href=\'/globalRanking.php?s=5&t=2\'>🥇 Global Ranking</a></div>';
        echo '<div class=\'btn-patron text-center\' style="margin-bottom: 10px"><a href=\'https://www.patreon.com/bePatron?u=' . config('services.patreon.user_id') . '\'>️💙 Become a Patron!</a></div>';
        if (config('services.discord.invite_id')) {
            echo '<div class=\'btn-discord text-center\' style="margin-bottom: 10px"><a href=\'https://discord.gg/' . config('services.discord.invite_id') . '\'>💬 Join us on Discord!</a></div>';
        }
        echo '<div class=\'text-center\' style="margin-bottom: 10px"><a href=\'https://www.youtube.com/channel/UCIGdJGxrzmNYMaAGPsk2sIA\'>🎙️ RAPodcast</a></div>';
        echo '<div class=\'text-center\' style="margin-bottom: 10px"><a href=\'https://news.retroachievements.org/\'>📰 RANews</a></div>';
        RenderDocsComponent();
        RenderAOTWComponent($staticData['Event_AOTW_AchievementID'], $staticData['Event_AOTW_ForumID']);
        RenderStaticDataComponent($staticData);
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
