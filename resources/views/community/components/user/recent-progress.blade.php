@props([
    'username' => '',
    'userScoreData' => [],
])

<?php
use Illuminate\Support\Carbon;
?>

<div class="component">
    <h3>Recent Progress</h3>

    @if (empty($userScoreData))
        <p>No points earned in the last 14 days.</p>
    @else
        <script defer src="https://www.gstatic.com/charts/loader.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof google !== 'undefined') {
                google.load('visualization', '1.0', { 'packages': ['corechart'] });
                google.setOnLoadCallback(drawCharts);
            }
        });

        function drawCharts() {
            var dataRecentProgress = new google.visualization.DataTable();

            // Declare columns
            dataRecentProgress.addColumn('date', 'Date');    // NOT date! this is non-continuous data
            dataRecentProgress.addColumn('number', 'Hardcore Score');
            dataRecentProgress.addColumn('number', 'Softcore Score');

            dataRecentProgress.addRows([
                @php
                    $count = 0;
                    foreach ($userScoreData as $dayInfo) {
                        if ($count++ > 0) {
                            echo ", ";
                        }

                        $nextDate = Carbon::parse($dayInfo['Date']);
                        $nextYear = $nextDate->year;
                        $nextMonth = $nextDate->month;
                        $nextDay = $nextDate->day;
                        $dateStr = $nextDate->format('d M Y');

                        $hardcoreValue = $dayInfo['CumulHardcoreScore'];
                        $softcoreValue = $dayInfo['CumulSoftcoreScore'];

                        echo "[ {v:new Date($nextYear,$nextMonth,$nextDay), f:'$dateStr'}, $hardcoreValue, $softcoreValue ]";
                    }
                @endphp
            ]);

            var optionsRecentProcess = {
                backgroundColor: 'transparent',
                title: 'Recent Progress',
                titleTextStyle: { color: '#186DEE' },
                hAxis: {
                    textStyle: { color: '#186DEE' },
                    slantedTextAngle: 90
                },
                vAxis: { textStyle: { color: '#186DEE' } },
                legend: { position: 'none' },
                chartArea: {
                    left: 42,
                    width: 458,
                    'height': '100%'
                },
                showRowNumber: false,
                view: { columns: [0, 1] },
                colors: ['#cc9900', '#737373'],
            };

            function resize() {
                chartRecentProgress = new google.visualization.AreaChart(document.getElementById('chart_recentprogress'));
                chartRecentProgress.draw(dataRecentProgress, optionsRecentProcess);
            }

            window.onload = resize();
            window.onresize = resize;
        }
        </script>

        <div id="chart_recentprogress" class="mb-5 min-h-[200px]"></div>
        <div class="text-right">
            <a href="/history.php?u={{ $username }}">more...</a>
        </div>
    @endif
</div>
