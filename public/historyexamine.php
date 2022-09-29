<?php

use RA\UnlockMode;

authenticateFromCookie($user, $permissions, $userDetails);

$userPage = request()->query('u');
if (empty($userPage)) {
    abort(404);
}

getUserPageInfo($userPage, $userMassData, 0, 0, $user);
if (!$userMassData) {
    abort(404);
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

RenderContentStart("$userPage's Legacy - $dateStr");
?>
<script>
  function convertDate() {
    var field = document.gotodateform.dateinput;
    var timestamp = new Date(field.value).getTime() / 1000;
    document.gotodateform.d.value = timestamp;
    return true;
 }
</script>
<div id="mainpage">
    <div id='fullcontainer'>
        <?php
        echo "<div class='navpath'>";
        echo "<a href='/userList.php'>All Users</a>";
        echo " &raquo; <a href='/user/$userPage'>$userPage</a>";
        echo " &raquo; <a href='history.php?u=$userPage'>History</a>";
        echo " &raquo; <b>$dateStr</b>";
        echo "</div>";

        echo "<h3>History</h3>";

        echo "<div>";
        echo "<img src='" . media_asset('/UserPic/' . $userPage . '.png') . "' alt='$userPage' align='right' width='64' height='64'>";
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

        echo "<table><tbody>";

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

        $achEarnedLib = [];
        foreach ($achEarnedOnDay as $achEarned) {
            $achID = $achEarned['AchievementID'];
            $achPoints = $achEarned['Points'];

            // capture the entry if it's a hardcore unlock, or a hardcore unlock has not yet been seen
            if ($achEarned['HardcoreMode'] == UnlockMode::Hardcore) {
                $achEarnedLib[$achID] = $achEarned;
                $achEarnedLib[$achID]['PointsNote'] = "$achPoints";
            } elseif (!isset($achEarnedLib[$achID])) {
                $achEarnedLib[$achID] = $achEarned;
                $achEarnedLib[$achID]['PointsNote'] = "<span class='softcore'>$achPoints</span>";
            }
        }

        usort($achEarnedLib, fn ($a, $b) => $a['Date'] <=> $b['Date']);

        foreach ($achEarnedLib as $achEarned) {
            $achAwardedAt = $achEarned['Date'];
            $achDesc = $achEarned['Description'];
            $achPoints = $achEarned['Points'];
            $achPointsNote = $achEarned['PointsNote'] ?? '';
            $achAuthor = $achEarned['Author'];

            sanitize_outputs($achDesc);

            $pointsCount += $achPoints;
            $earnedCount++;
            // $dateUnix = strtotime( "$nextDay-$nextMonth-$nextYear" );
            // $dateStr = getNiceDate( $dateUnix, true );

            echo "<tr>";

            echo "<td>";
            echo getNiceTime(strtotime($achAwardedAt));
            echo "</td>";

            echo "<td style='min-width:25%'>";
            echo achievementAvatar($achEarned);
            echo "</td>";

            echo "<td style='min-width:25%'>";
            echo "$achDesc";
            echo "</td>";

            echo "<td class='whitespace-nowrap'>";
            echo "$achPointsNote";
            echo "</td>";

            echo "<td>";
            echo userAvatar($achAuthor, label: false);
            echo "</td>";

            echo "<td>";
            echo gameAvatar($achEarned, label: false);
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
<?php RenderContentEnd(); ?>
