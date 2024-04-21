<?php

use App\Models\User;
use Carbon\Carbon;

authenticateFromCookie($user, $permissions, $userDetails);

$userPage = requestInputSanitized('u', $user);

if (!isset($userPage) || !isValidUsername($userPage)) {
    abort(404);
}

$userDetails = User::firstWhere('User', $userPage);
if (!$userDetails) {
    abort(404);
}

$listOffset = requestInputSanitized('o', 0, 'integer');
$sortBy = requestInputSanitized('s', 3, 'integer');
$maxDays = requestInputSanitized('c', 15, 'integer');
$userBestDaysList = getUserBestDaysList($userDetails, $listOffset, $maxDays, $sortBy);
$date = requestInputSanitized('d', date("Y-m-d"));
$dateUnix = strtotime("$date");

$sortByGraphName = 'Total Points';
if ($sortBy == 2 || $sortBy == 12) {
    $sortByGraphName = 'Num Achievements Earned';
}

$userPageHardcorePoints = $userDetails->RAPoints;
$userPageSoftcorePoints = $userDetails->RASoftcorePoints;

//	the past week
$userScoreData = getAwardedList($userDetails);
?>
<x-app-layout pageTitle="{{ $userPage }}'s History">
<script defer src="https://www.gstatic.com/charts/loader.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    google.load('visualization', '1.0', { 'packages': ['corechart'] });
    google.setOnLoadCallback(drawCharts);
  });

  function drawCharts() {
    var dataTotalScore = new google.visualization.DataTable();

    // Declare columns
    dataTotalScore.addColumn('date', 'Date Earned');
    dataTotalScore.addColumn('number', 'Hardcore Score');
    dataTotalScore.addColumn('number', 'Softcore Score');

    dataTotalScore.addRows([
        <?php
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

            echo "[ {v:new Date($nextYear," . ($nextMonth - 1) . ",$nextDay), f:'$dateStr'}, $hardcoreValue, $softcoreValue ]";
        }
        ?>
    ]);

    var optionsTotalScore = {
      backgroundColor: 'transparent',
      title: '<?= $sortByGraphName ?>',
      titleTextStyle: { color: '#186DEE' }, // cc9900
      hAxis: { textStyle: { color: '#186DEE' }, slantedTextAngle: 90 },
      vAxis: { textStyle: { color: '#186DEE' } },
      legend: { position: 'none' },
      chartArea: { 'width': '86%', 'height': '70%' },
      height: 250,
      colors: ['#cc9900','#737373'],
    };

    var dataBestDays = new google.visualization.DataTable();

    // Declare columns
    dataBestDays.addColumn('string', 'Date'); // NOT date! this is non-continuous data
    dataBestDays.addColumn('number', 'Points Earned');

    dataBestDays.addRows([
        <?php
        $arrayToUse = $userBestDaysList;
        if ($sortBy == 1 || $sortBy > 11) {
            $arrayToUse = array_reverse($userBestDaysList);
        }

        $count = 0;
        foreach ($arrayToUse as $dayInfo) {
            if ($count++ > 0) {
                echo ", ";
            }

            $dateStr = Carbon::parse($dayInfo['Date'])->format("d M Y");
            $nextNumAwarded = $dayInfo['NumAwarded'];
            $nextTotalPointsEarned = $dayInfo['TotalPointsEarned'];

            $value = $nextTotalPointsEarned;
            if ($sortBy == 2 || $sortBy == 12) {
                $value = $nextNumAwarded;
            }

            echo "['$dateStr', $value]";
        }
        ?>
    ]);

    var optionsBestDays = {
      backgroundColor: 'transparent',
      title: '<?= $sortByGraphName ?>',
      titleTextStyle: { color: '#186DEE' },
      hAxis: { textStyle: { color: '#186DEE' }, slantedTextAngle: 90 },
      vAxis: { textStyle: { color: '#186DEE' } },
      legend: { position: 'none' },
      chartArea: { 'width': '90%', 'height': '70%' },
      showRowNumber: false,
      view: { columns: [0, 1] },
      height: 250,
      colors: ['#cc9900'],
    };

    var chartBestDays;
    var chartScoreProgress;

    function selectHandlerBestDays(e) {
      if (chartBestDays.getSelection().length >= 1) {
        var dateAbbr = dataBestDays.getFormattedValue(chartBestDays.getSelection()[0].row, 0);
        var dateParsed = Date.parse(dateAbbr) / 1000;
        window.location = '/historyexamine.php?d=' + dateParsed + '&u=<?= $userPage ?>';
      }
    }

    function selectHandlerScoreProgress(e) {
      if (chartScoreProgress.getSelection().length >= 1) {
        var dateFormatted = dataTotalScore.getFormattedValue(chartScoreProgress.getSelection()[0].row, 0);

        var d = new Date(Date.parse(dateFormatted));
        var dAdj = new Date(d.getTime() + 60000 * 60 * 12);	// Adjusted by 60000 (min) times 60 (hour) times 12 (middle of day)

        var nUnix = parseInt(dAdj.getTime() / 1000);

        window.location = '/historyexamine.php?d=' + nUnix + '&u=' + <?php echo "'$userPage'"; ?>;
      }
    }

    function resize() {
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

<script>
  function convertDate() {
    const { dateinput, d } = document.gotodateform;
    const timestamp = new Date(dateinput.value).getTime() / 1000;
    d.value = timestamp;
    return true;
  }
</script>

    <?php
    echo "<div class='navpath'>";
    echo "<a href='/userList.php'>All Users</a>";
    echo " &raquo; <a href='/user/$userPage'>$userPage</a>";
    echo " &raquo; <b>History</b>";
    echo "</div>";
    ?>

    <?php if ($user !== null): ?>
        <div class="flex flex-wrap justify-between">
            <div>
            </div>
            <div>
                <form action="history.php">
                    <label>
                        Filter by user:<br>
                        <input size="28" name="u" type="text" value="<?= $userPage ?>">
                    </label>
                    <button class="btn">Select</button>
                </form>
            </div>
        </div>
    <?php endif ?>
    <?php

    echo "<h3>History</h3>";

    echo "<div>";
    echo "<img src='" . media_asset('/UserPic/' . $userPage . '.png') . "' alt='$userPage' align='right' width='64' height='64' class='rounded-sm'>";
    echo "<b><a href='/user/$userPage'><strong>$userPage</strong></a> ";
    if ($userPageHardcorePoints > 0) {
        echo "(" . localized_number($userPageHardcorePoints) . ") ";
    }
    if ($userPageSoftcorePoints > 0) {
        echo "<span class ='softcore'>(" . localized_number($userPageSoftcorePoints) . " softcore)</span>";
    }
    echo "</b><br>";

    echo "Member since: " . getNiceDate(strtotime($userDetails->created_at)) . "<br>";
    echo "<br>";
    echo "<form name='gotodateform' action='/historyexamine.php' onsubmit='convertDate()'>";
    echo "<label for='d' class='font-bold'>Jump to Date: </label>";
    echo "<input type='date' id='dateinput' value='" . strftime("%Y-%m-%d", $dateUnix) . "' />";
    echo "<input type='hidden' name='d' value='$dateUnix' />";
    echo "<input type='hidden' name='u' value='$userPage' />";
    echo "<button class='btn ml-1'>Go to Date</button>";
    echo "</form>";

    echo "</div>";

    echo "<div id='chart_scoreprogress' class='min-h-[250px]'></div>";

    echo "<h3>Best Days</h3>";
    echo "<div id='chart_bestdays' class='min-h-[250px]'></div>";

    echo "<table class='mt-4 table-highlight'><tbody>";

    $sort1 = ($sortBy == 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;

    echo "<tr class='do-not-highlight'>";
    echo "<th><a href='/history.php?s=$sort1&u=$userPage'>Date</a></th>";
    echo "<th class='text-right'><a href='/history.php?s=$sort2&u=$userPage'>Num Achievements</a></th>";
    echo "<th class='text-right'><a href='/history.php?s=$sort3&u=$userPage'>Score Earned</a></th>";
    echo "</tr>";

    $dayCount = 0;
    foreach ($userBestDaysList as $dayInfo) {
        $nextNumAwarded = $dayInfo['NumAwarded'];
        $nextTotalPointsEarned = $dayInfo['TotalPointsEarned'];
        $date = Carbon::parse($dayInfo['Date']);
        $dateUnix = $date->unix();
        $dateStr = $date->format("d M Y");

        echo "<tr>";
        echo "<td>$dateStr</td>";
        echo "<td class='text-right'><a href='historyexamine.php?d=$dateUnix&u=$userPage'>$nextNumAwarded</a></td>";
        echo "<td class='text-right'><a href='historyexamine.php?d=$dateUnix&u=$userPage'>" . localized_number($nextTotalPointsEarned) . "</a></td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    ?>
</x-app-layout>
