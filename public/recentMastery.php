<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$maxCount = 25;
// First day with Game Awards
$minDate = '2014-09-29';

$errorCode = requestInputSanitized('e');
$offset = requestInputSanitized('o', 0, 'integer');
$offset = max($offset, 0);
$friends = requestInputSanitized('f', 0, 'integer');
$date = requestInputSanitized('d', date("Y-m-d"));

$lbUsers = $friends === 1 ? 'Friends' : '';

if ($friends == 1) {
    $data = getRecentMasteryData($date, $user, $offset, $maxCount + 1);
} else {
    $data = getRecentMasteryData($date, null, $offset, $maxCount + 1);
}

RenderHtmlStart();
RenderHtmlHead("Recent " . $lbUsers . " Masteries");
?>
<body>
<?php
RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions);
RenderToolbar($user, $permissions);
?>
<div id='mainpage'>
    <div id='fullcontainer'>
        <?php
        RenderErrorCodeWarning($errorCode);
        echo "<h2 class='longheader'>Recent " . $lbUsers . " Masteries</h2>";

        // Add the leaderboard filters
        echo "<div class='embedded mb-1'>";

        // Create the Users filters only if a user is logged in
        if ($user !== null) {
            echo "<div>";
            echo "<b>Users:</b> ";
            if ($friends == 0) {
                echo "<b><a href='/recentMastery.php?d=$date&f=0'>*All Users</a></b> | ";
            } else {
                echo "<a href='/recentMastery.php?d=$date&f=0'>All Users</a> | ";
            }
            if ($friends == 1) {
                echo "<b><a href='/recentMastery.php?d=$date&f=1'>*Friends Only</a></b>";
            } else {
                echo "<a href='/recentMastery.php?d=$date&f=1'>Friends Only</a>";
            }
            echo "</div>";
        }

        // Create the custom date filter
        echo "<form action='/recentMastery.php' method='get'>";
        echo "<label for='d'><b>Jump to Date: </b></label>";
        echo "<input type='hidden' name='t' value=" . 0 . ">";
        echo "<input type='date' name='d' value=" . $date . " min=$minDate max=" . date("Y-m-d") . "> ";
        echo "<input type='hidden' name='f' value=" . $friends . ">";
        echo "<input type='submit' value='Goto Date' />";
        echo "</form>";

        // Clear filter
        if ($date != date("Y-m-d") || $friends != 0) {
            echo "<div>";
            echo "<a href='/recentMastery.php'>Clear Filter</a>";
            echo "</div>";
        }
        echo "</div>";

        echo "<table><tbody>";

        // Headers
        echo "<tr>";
        echo "<th>User</th>";
        echo "<th>Type</th>";
        echo "<th>Game</th>";
        echo "<th>Date</th>";
        echo "</tr>";

        $userCount = 0;
        $skip = false;
        // Create the table rows
        foreach ($data as $dataPoint) {
            // Break if we have hit the maxCount + 1 user
            if ($userCount == $maxCount) {
                $userCount++;
                $skip = true;
            }

            if (!$skip) {
                echo "<tr>";

                echo "<td>";
                echo GetUserAndTooltipDiv($dataPoint['User'], true);
                echo GetUserAndTooltipDiv($dataPoint['User'], false);
                echo "</td>";

                echo "<td>";
                if ($dataPoint['AwardDataExtra'] == 1) {
                    echo "Mastered";
                } else {
                    echo "Completed";
                }
                echo "</td>";

                echo "<td>";
                echo GetGameAndTooltipDiv($dataPoint['GameID'], $dataPoint['GameTitle'], $dataPoint['GameIcon'], $dataPoint['ConsoleName']);
                echo "</td>";

                echo "<td>";
                echo $dataPoint['AwardedAt'];
                echo "</td>";

                echo "</tr>";
                $userCount++;
            }
        }
        echo "</tbody></table>";

        // Add page traversal
        echo "<div class='rightalign row'>";
        if ($date > $minDate) {
            $prevDate = date('Y-m-d', strtotime($date . "-1 days"));
            echo "<a href='/recentMastery.php?d=$prevDate&f=$friends&o=0'>&lt; Prev Day </a>";
            if ($date < date("Y-m-d")) {
                echo " | ";
            }
        }
        if ($offset > 0) {
            $prevOffset = $offset - $maxCount;
            echo "<a href='/recentMastery.php?d=$date&f=$friends&o=$prevOffset'>&lt; Prev $maxCount </a>";
        }
        if ($userCount > $maxCount) {
            if ($offset > 0) {
                echo " - ";
            }
            $nextOffset = $offset + $maxCount;
            echo "<a href='/recentMastery.php?d=$date&f=$friends&o=$nextOffset'>Next $maxCount &gt;</a>";
        }
        if ($date < date("Y-m-d")) {
            $nextDate = date('Y-m-d', strtotime($date . "+1 days"));
            echo " | <a href='/recentMastery.php?d=$nextDate&f=$friends&o=0'>Next Day &gt;</a>";
        }
        echo "</div>";
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
