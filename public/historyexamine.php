<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

use RA\UnlockMode;

authenticateFromCookie($user, $permissions, $userDetails);

$userPage = requestInputSanitized('u', $user);
if (!isset($userPage)) {
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

getUserPageInfo($userPage, $userMassData, 0, 0, $user);
if (!$userMassData) {
    http_response_code(404);
    echo "User not found";
    exit;
}

$dateInput = requestInputSanitized('d', 0);

$userPageHardcorePoints = 0;
$userPageSoftcorePoints = 0;

if (getPlayerPoints($userPage, $userPoints)) {
    $userPageHardcorePoints = $userPoints['RAPoints'];
    $userPageSoftcorePoints = $userPoints['RASoftcorePoints'];
}

$achEarnedOnDay = getAchievementsEarnedOnDay($dateInput, $userPage);

$dateStr = strftime("%d %b %Y", $dateInput);

$errorCode = requestInputSanitized('e');

RenderHtmlStart(true);
RenderHtmlHead("$userPage's Legacy - $dateStr");
?>
<body>
<script>
  function convertDate() {
    var field = document.gotodateform.dateinput;
    var timestamp = new Date(field.value).getTime() / 1000;
    document.gotodateform.d.value = timestamp;
    return true;
 }
</script>
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <div id='fullcontainer'>
        <?php
        echo "<div class='navpath'>";
        echo "<a href='/userList.php'>All Users</a>";
        echo " &raquo; <a href='/user/$userPage'>$userPage</a>";
        echo " &raquo; <a href='history.php?u=$userPage'>History</a>";
        echo " &raquo; <b>$dateStr</b>";
        echo "</div>";

        echo "<h3 class='longheader'>History</h3>";

        echo "<div class='userlegacy'>";
        echo "<img src='/UserPic/$userPage.png' alt='$userPage' align='right' width='64' height='64'>";
        echo "<b><a href='/user/$userPage'><strong>$userPage</strong></a> ";

        if ($userPageHardcorePoints > 0) {
            echo "($userPageHardcorePoints) ";
        }
        if ($userPageSoftcorePoints > 0) {
            echo "<span class='softcore'>($userPageSoftcorePoints softcore)</span>";
        }

        echo "</b><br>";
        echo "<form name='gotodateform' action='/historyexamine.php' onsubmit='convertDate()'>";
        echo "<label for='d'><b>Jump to Date: </b></label>";
        echo "<input type='date' id='dateinput' value='" . strftime("%Y-%m-%d", $dateInput) . "' />";
        echo "<input type='hidden' name='d' value='$dateInput' />";
        echo "<input type='hidden' name='u' value='$userPage' />";
        echo "<input type='submit' value='Goto Date'/>";
        echo "</form>";
        echo "<br>";

        echo "<br>";

        echo "</div>";

        echo "<table class='smalltable xsmall'><tbody>";

        echo "<tr>";
        echo "<th>When</th>";
        echo "<th>Achievement</th>";
        echo "<th>Description</th>";
        echo "<th>Points</th>";
        echo "<th>Author</th>";
        echo "<th>Game</th>";
        echo "</tr>";

        // Merge if poss and count
        $achCount = count($achEarnedOnDay);
        $earnedCount = 0;
        $pointsCount = 0;
        // foreach( $achEarnedOnDay as $achEarned )

        $achEarnedLib = [];

        // Potentially overwrite HARDCORE into $achEarnedLib
        for ($i = 0; $i < $achCount; $i++) {
            $achID = $achEarnedOnDay[$i]['AchievementID'];
            $achEarnedLib[$achID] = $achEarnedOnDay[$i];
            $achPoints = $achEarnedLib[$achID]['Points'];
            if ($achEarnedOnDay[$i]['HardcoreMode'] == UnlockMode::Hardcore) {
                $achEarnedLib[$achID]['PointsNote'] = "$achPoints";
            } else { // else Softcore
                $achEarnedLib[$achID]['PointsNote'] = "<span class='softcore'>$achPoints</span>";
            }
        }

        usort($achEarnedLib, fn ($a, $b) => $a['Date'] <=> $b['Date']);

        foreach ($achEarnedLib as $achEarned) {
            $achAwardedAt = $achEarned['Date'];
            $achID = $achEarned['AchievementID'];
            $achTitle = $achEarned['Title'];
            $achDesc = $achEarned['Description'];
            $achPoints = $achEarned['Points'];
            $achPointsNote = $achEarned['PointsNote'] ?? '';
            $achAuthor = $achEarned['Author'];
            $achGameID = $achEarned['GameID'];
            $achGameTitle = $achEarned['GameTitle'];
            $achGameIcon = $achEarned['GameIcon'];
            $achConsoleName = $achEarned['ConsoleName'];
            $achBadgeName = $achEarned['BadgeName'];
            $hardcoreMode = $achEarned['HardcoreMode'];

            sanitize_outputs($achTitle, $achDesc);

            $pointsCount += $achPoints;
            $earnedCount++;
            // $dateUnix = strtotime( "$nextDay-$nextMonth-$nextYear" );
            // $dateStr = getNiceDate( $dateUnix, TRUE );

            echo "<tr>";

            echo "<td>";
            echo getNiceTime(strtotime($achAwardedAt));
            echo "</td>";

            echo "<td style='min-width:25%'>";
            echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $achGameTitle, $achBadgeName, true, false, '', 32, $hardcoreMode ? 'goldimage' : '');
            echo "</td>";

            echo "<td style='min-width:25%'>";
            echo "$achDesc";
            echo "</td>";

            echo "<td nowrap>";
            echo "$achPointsNote";
            echo "</td>";

            echo "<td>";
            echo GetUserAndTooltipDiv($achAuthor, true);
            echo "</td>";

            echo "<td>";
            echo GetGameAndTooltipDiv($achGameID, $achGameTitle, $achGameIcon, $achConsoleName, true, 32);
            echo "</td>";

            echo "</tr>";
        }

        echo "</tbody></table>";

        echo "<h3>Summary</h3>";
        echo "<div class='historyexaminesummary'>";
        echo "Total earned on $dateStr: <strong>$pointsCount</strong> points, <strong>$earnedCount</strong> achievements.<br><br>";
        echo "<a href='/history.php?u=$userPage'>&laquo; Back to $userPage's Legacy</a><br><br>";
        echo "</div>";
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
