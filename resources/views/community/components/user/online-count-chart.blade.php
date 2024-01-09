<?php

use App\Site\Models\User;
use Illuminate\Support\Carbon;

$playersOnlineChartData = [];
if (file_exists("../storage/logs/playersonline.log")) {
    $playersOnlineCSV = file_get_contents("../storage/logs/playersonline.log");

    $playersCsv = preg_split('/\n|\r\n?/', $playersOnlineCSV);
    $playersCsvCount = is_countable($playersCsv) ? count($playersCsv) : 0;

    for ($i = 0; $i < 48; $i++) {
        if (isset($playersCsv[$playersCsvCount - ($i + 2)])) {
            $playersOnlineChartData[] = $playersCsv[count($playersCsv) - ($i + 2)];
        }
    }
}

$numPlayers = User::where('LastLogin', '>', Carbon::now()->subMinutes(10))->count();
?>

<script defer src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        google.load('visualization', '1.0', { 'packages': ['corechart'] });
        google.setOnLoadCallback(drawCharts);
    });

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
                if (count($playersOnlineChartData) < $i) {
                    continue;
                }
                $players = empty($playersOnlineChartData[$i]) ? 0 : $playersOnlineChartData[$i];
                $players = (int) $players;
                $localizedPlayers = localized_number($players);

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

                echo "[ new Date($yr,$month,$day,$hour,$min), {v:$players, f:\"$localizedPlayers\"} ] ";
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
            vAxis: {
                textStyle: { color: '#186DEE' },
                viewWindow: { min: 0 },
                format: '#'
            },
            legend: { position: 'none' },
            chartArea: {
                left: '8%',
                right: '2%',
                'width': '100%',
                'height': '78%'
            },
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

<div class="component">
    <h3>Currently Online</h3>
    <div id="playersonlinebox" class="infobox">
        <div>There are currently <strong>{{ localized_number($numPlayers) }}</strong> players online.</div>
    </div>
    <div style="min-height: 160px;" id="chart_usersonline"></div>
    <div class="text-right lastupdatedtext"><small><span id="playersonline-update"></span></small></div>
</div>
