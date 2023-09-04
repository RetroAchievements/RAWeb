<?php

authenticateFromCookie($user, $permissions, $userDetails);

$userPage = requestInputSanitized('u', $user);

if (!isset($userPage) || !isValidUsername($userPage)) {
    abort(404);
}

if (!getAccountDetails($userPage, $userDetails)) {
    abort(404);
}

$listOffset = requestInputSanitized('o', 0, 'integer');
$sortBy = requestInputSanitized('s', 3, 'integer');
$maxDays = requestInputSanitized('c', 15, 'integer');
$userBestDaysList = getUserBestDaysList($userPage, $listOffset, $maxDays, $sortBy);
$date = requestInputSanitized('d', date("Y-m-d"));
$dateUnix = strtotime("$date");

$sortByGraphName = 'Total Points';
if ($sortBy == 2 || $sortBy == 12) {
    $sortByGraphName = 'Num Achievements Earned';
}

$userPageHardcorePoints = $userDetails['RAPoints'];
$userPageSoftcorePoints = $userDetails['RASoftcorePoints'];

getUserActivityRange($userPage, $userSignedUp, $userLastLogin);

//	the past week
$userScoreData = getAwardedList($userPage);

RenderContentStart("$userPage's Legacy");
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

            $nextDay = (int) $dayInfo['Day'];
            $nextMonth = (int) $dayInfo['Month'] - 1;
            $nextYear = (int) $dayInfo['Year'];
            $nextDate = $dayInfo['Date'];

            $dateStr = !empty($nextDate) ? getNiceDate(strtotime($nextDate), true) : '';
            $hardcoreValue = $dayInfo['CumulHardcoreScore'];
            $softcoreValue = $dayInfo['CumulSoftcoreScore'];

            echo "[ {v:new Date($nextYear,$nextMonth,$nextDay), f:'$dateStr'}, $hardcoreValue, $softcoreValue ]";
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
      colors: ['#186DEE','#8c8c8c'],
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

            $nextDay = $dayInfo['Day'];
            $nextMonth = $dayInfo['Month'];
            $nextYear = $dayInfo['Year'];

            $dateStr = "$nextDay/$nextMonth";
            if ($nextYear != date('Y')) {
                $dateStr = "$nextDay/$nextMonth/$nextYear";
            }

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
        var firstSlashAt = dateAbbr.indexOf('/');
        var secondSlashAt = dateAbbr.lastIndexOf('/');

        var d = new Date;

        var day = dateAbbr.split('/')[0];
        var month = dateAbbr.split('/')[1];

        if (firstSlashAt != secondSlashAt) {
          d.setFullYear(dateAbbr.split('/')[2], month - 1, day);
        } else {
          d.setFullYear(new Date().getFullYear(), month - 1, day);
        }
        window.location = '/historyexamine.php?d=' + parseInt(d.getTime() / 1000) + '&u=' + <?php echo "'$userPage'"; ?>;
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

<article>
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

    $memberSince = !empty($userSignedUp) ? getNiceDate(strtotime($userSignedUp), true) : '';
    echo "Member since: " . $memberSince . "<br>";
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
        $nextDay = $dayInfo['Day'];
        $nextMonth = $dayInfo['Month'];
        $nextYear = $dayInfo['Year'];
        $nextNumAwarded = $dayInfo['NumAwarded'];
        $nextTotalPointsEarned = $dayInfo['TotalPointsEarned'];

        $dateUnix = strtotime("$nextDay-$nextMonth-$nextYear");
        $dateStr = getNiceDate($dateUnix, true);

        echo "<tr>";
        echo "<td>$dateStr</td>";
        echo "<td class='text-right'><a href='historyexamine.php?d=$dateUnix&u=$userPage'>$nextNumAwarded</a></td>";
        echo "<td class='text-right'><a href='historyexamine.php?d=$dateUnix&u=$userPage'>" . localized_number($nextTotalPointsEarned) . "</a></td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    ?>
</article>
<?php RenderContentEnd(); ?>
