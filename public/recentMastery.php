<?php
require_once __DIR__ . '/../vendor/autoload.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$maxCount = 25;

$errorCode = requestInputSanitized('e');
$offset = requestInputSanitized('o', 0, 'integer');
$offset = max($offset, 0);
$sort = requestInputSanitized('s', 5, 'integer');
$type = requestInputSanitized('t', 0, 'integer');
$friends = requestInputSanitized('f', 0, 'integer');
$untracked = requestInputSanitized('u', 0, 'integer');
$date = requestInputSanitized('d', date("Y-m-d"));
$dateUnix = strtotime("$date");

switch ($type) {
    case 0: // Daily
        $lbType = "Daily";
        break;
    case 2: // All Time
        $lbType = "All Time";

        // Set default sorting if the user switches to All Time with an invalid All Time sorting selected.
        if (($sort % 10) != 5 && ($sort % 10) != 6 && ($sort % 10) != 7) {
            $sort = 5;
        }
        break;
    default:
        $lbType = "";
        break;
}

switch ($friends) {
    case 0: // Global
        $lbUsers = "";
        break;
    case 1: // Friends
        $lbUsers = "Friends";
        break;
    default:
        $lbUsers = "";
        break;
}

if ($friends == 1) {
    $data = getRecentMasteryData($type, $sort, $date, null, $user, $untracked, $offset, $maxCount);
} else {
    $data = getRecentMasteryData($type, $sort, $date, $user, null, $untracked, $offset, $maxCount);
}

RenderHtmlStart();
RenderHtmlHead("Recent " . $lbUsers . " Masteries - " . $lbType);
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
        echo "<h2 class='longheader'>Recent " . $lbUsers . " Masteries - " . $lbType . "</h2>";

        // Add the leaderboard filters
        echo "<div class='embedded mb-1'>";

        // Create the Leaderboard Type filters
        echo "<div>";
        echo "<b>Period:</b> ";
        if ($type == 0) {
            echo "<b><a href='/recentMastery.php?s=$sort&t=0&d=$date&f=$friends'>*Daily</a></b> | ";
        } else {
            echo "<a href='/recentMastery.php?s=$sort&t=0&d=$date&f=$friends'>Daily</a> | ";
        }
        if ($type == 1) {
            echo "<b><a href='/recentMastery.php?s=$sort&t=1&d=$date&f=$friends'>*All Time</a></b>";
        } else {
            echo "<a href='/recentMastery.php?s=$sort&t=1&d=$date&f=$friends'>All Time</a>";
        }
        echo "</div>";

        // Create the Users filters only if a user is logged in
        if ($user !== null) {
            echo "<div>";
            echo "<b>Users:</b> ";
            if ($friends == 0) {
                echo "<b><a href='/recentMastery.php?s=$sort&t=$type&d=$date&f=0'>*All Users</a></b> | ";
            } else {
                echo "<a href='/recentMastery.php?s=$sort&t=$type&d=$date&f=0'>All Users</a> | ";
            }
            if ($friends == 1) {
                echo "<b><a href='/recentMastery.php?s=$sort&t=$type&d=$date&f=1'>*Friends Only</a></b>";
            } else {
                echo "<a href='/recentMastery.php?s=$sort&t=$type&d=$date&f=1'>Friends Only</a>";
            }
            echo "</div>";
        }

        // Create the custom date folter
        echo "<form action='/recentMastery.php' method='get'>";
        echo "<label for='d'><b>Custom Date: </b></label>";
        echo "<input type='hidden' name='s' value=" . $sort . ">";
        echo "<input type='hidden' name='t' value=" . 0 . ">";
        echo "<input type='date' name='d' value=" . $date . " min='2012-01-01' max=" . date("Y-m-d") . "> ";
        echo "<input type='hidden' name='f' value=" . $friends . ">";
        echo "<input type='submit' value='Goto Date' />";
        echo "</form>";

        // Clear filter
        if ($sort != 5 || $type != 0 || $date != date("Y-m-d") || $friends != 0) {
            echo "<div>";
            echo "<a href='/recentMastery.php'>Clear Filter</a>";
            echo "</div>";
        }
        echo "</div>";

        // Toggle ascending or descending sorting
        // $sort8 = ($sort == 8) ? 18 : 8; // Completed Awards
        // $sort9 = ($sort == 9) ? 19 : 9; // Mastered Awards

        echo "<table><tbody>";

        // User header
        echo "<th>User</th>";

        // // Sortable Mastered Awards header
        // if ($type == 2) {
        //     echo "(Mastered)</th>";
        // } else {
        //     if (($sort % 10) == 9) {
        //         if ($sort9 == 9) {
        //             echo "<b><a href='/recentMastery.php?s=$sort9&t=$type&d=$date&f=$friends'>(Mastered) &#9650;</a></b></th>";
        //         } else {
        //             echo "<b><a href='/recentMastery.php?s=$sort9&t=$type&d=$date&f=$friends'>(Mastered) &#9660;</a></b></th>";
        //         }
        //     } else {
        //         echo "<a href='/recentMastery.php?s=$sort9&t=$type&d=$date&f=$friends'>(Mastered)</a></th>";
        //     }
        // }

        echo "<th>Type</th>";
        echo "<th>Game</th>";
        echo "<th>Date</th>";
        echo "</tr>";

        $userCount = 0;
        // Create the table rows
        foreach ($data as $dataPoint) {
            // Break if we have hit the maxCount + 1 user
            if ($userCount == $maxCount) {
                $userCount++;
                $findUserRank = true;
            }

            echo "<tr>";

            echo "<td>";
            echo GetUserAndTooltipDiv($dataPoint['User'], true);
            echo GetUserAndTooltipDiv($dataPoint['User'], false);
            echo "</td>";

            echo "<td>";
            if ($dataPoint['AwardDataExtra'] == 1){
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
        echo "</tbody></table>";

        // Add page traversal
        echo "<div class='rightalign row'>";
        if ($offset > 0) {
            $prevOffset = $offset - $maxCount;
            echo "<a href='/recentMastery.php?s=$sort&t=$type&d=$date&f=$friends'>First</a> - ";
            echo "<a href='/recentMastery.php?s=$sort&t=$type&d=$date&f=$friends&o=$prevOffset'>&lt; Previous $maxCount</a> - ";
        }
        if ($userCount >= $maxCount) {
            $nextOffset = $offset + $maxCount;
            echo "<a href='/recentMastery.php?s=$sort&t=$type&d=$date&f=$friends&o=$nextOffset'>Next $maxCount &gt;</a>";
        }
        echo "</div>";
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
