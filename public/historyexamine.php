<?php

use App\Platform\Enums\UnlockMode;

authenticateFromCookie($user, $permissions, $userDetails);

$userPage = request()->query('u');
if (empty($userPage)) {
    abort(404);
}

if (!getAccountDetails($userPage, $userDetails)) {
    abort(404);
}

$dateInput = (int) request()->input('d', 0);

$userPageHardcorePoints = $userDetails['RAPoints'];
$userPageSoftcorePoints = $userDetails['RASoftcorePoints'];

$achEarnedOnDay = getAchievementsEarnedOnDay($dateInput, $userPage);

$dateStr = strftime("%d %b %Y", $dateInput);

RenderContentStart("$userPage's Legacy - $dateStr");
?>
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
    echo "<label for='d' class='font-bold'>Jump to Date: </label>";
    echo "<input type='date' id='dateinput' value='" . strftime("%Y-%m-%d", $dateInput) . "' />";
    echo "<input type='hidden' name='d' value='$dateInput' />";
    echo "<input type='hidden' name='u' value='$userPage' />";
    echo "<button class='btn ml-1'>Go to Date</button>";
    echo "</form>";
    echo "<br>";

    echo "<br>";

    echo "</div>";

    echo "<table class='table-highlight'><tbody>";

    echo "<tr class='do-not-highlight'>";
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
</article>
<?php RenderContentEnd(); ?>
