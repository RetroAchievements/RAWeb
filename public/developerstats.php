<?php

use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

authenticateFromCookie($user, $permissions, $userDetails);

$errorCode = requestInputSanitized('e');
$type = requestInputSanitized('t', 0, 'integer');
$defaultFilter = 7; // set all 3 status' to enabled
$devFilter = requestInputSanitized('f', 7, 'integer');

RenderHtmlStart();
RenderHtmlHead("Developer Stats");
?>
<body>
<?php
RenderHeader($userDetails);
?>
<div id='mainpage'>
    <div id='fullcontainer'>
        <h3 class='longheader'>Developer Stats</h3>
        <?php
        RenderErrorCodeWarning($errorCode);
        $devStatsList = GetDeveloperStatsFull(100, $type, $devFilter);

        echo "<div class='embedded mb-1'>";
        $activeDev = ($devFilter & (1 << 0));
        $juniorDev = ($devFilter & (1 << 1));
        $inactiveDev = ($devFilter & (1 << 2));

        echo "<div>";
        echo "<b>Developer Status:</b> ";
        if ($activeDev) {
            echo "<b><a href='/developerstats.php?t=$type&f=" . ($devFilter & ~(1 << 0)) . "'>*" . Permissions::toString(Permissions::Developer) . "</a></b> | ";
        } else {
            echo "<a href='/developerstats.php?t=$type&f=" . ($devFilter | (1 << 0)) . "'>" . Permissions::toString(Permissions::Developer) . "</a> | ";
        }

        if ($juniorDev) {
            echo "<b><a href='/developerstats.php?t=$type&f=" . ($devFilter & ~(1 << 1)) . "'>*" . Permissions::toString(Permissions::JuniorDeveloper) . "</a></b> | ";
        } else {
            echo "<a href='/developerstats.php?t=$type&f=" . ($devFilter | (1 << 1)) . "'>" . Permissions::toString(Permissions::JuniorDeveloper) . "</a> | ";
        }

        if ($inactiveDev) {
            echo "<b><a href='/developerstats.php?t=$type&f=" . ($devFilter & ~(1 << 2)) . "'>*Inactive</a></b>";
        } else {
            echo "<a href='/developerstats.php?t=$type&f=" . ($devFilter | (1 << 2)) . "'>Inactive</a>";
        }
        echo "</div>";

        // Clear Filter
        if ($devFilter != $defaultFilter) {
            echo "<div>";
            echo "<a href='/developerstats.php?t=$type&f=" . $defaultFilter . "'>Clear Filter</a>";
            echo "</div>";
        }

        echo "</div>";

        echo "<div class='rightfloat'>* = ordered by</div>";
        echo "<br style='clear:both;'>";
        echo "<div class='table-wrapper'><table><tbody>";
        echo "<th></th>";
        echo "<th><a href='/developerstats.php?t=6&f=$devFilter'>Name</a>" . ($type == 6 ? "*" : "") . "</th>";
        echo "<th class='text-right text-nowrap'><a href='/developerstats.php?t=3&f=$devFilter'>Open Tickets</a>" . ($type == 3 ? "*" : "") . "</th>";
        echo "<th class='text-right text-nowrap'><a href='/developerstats.php?f=$devFilter'>Achievements</a>" . ($type == 0 ? "*" : "") . "</th>";
        echo "<th class='text-right text-nowrap'><a href='/developerstats.php?t=4&f=$devFilter'>Ticket Ratio (%)</a>" . ($type == 4 ? "*" : "") . "</th>";
        echo "<th class='text-right'><a href='/developerstats.php?t=2&f=$devFilter' title='Achievements unlocked by others'>Yielded Unlocks</a>" . ($type == 2 ? "*" : "") . "</th>";
        echo "<th class='text-right'><a href='/developerstats.php?t=1&f=$devFilter' title='Points gained by others through achievement unlocks'>Yielded Points</a>" . ($type == 1 ? "*" : "") . "</th>";
        echo "<th class='text-right'><a href='/developerstats.php?t=7&f=$devFilter' title='Set claims currently active'>Active Claims</a>" . ($type == 7 ? "*" : "") . "</th>";
        // echo "<th class='text-right text-nowrap'><a href='/developerstats.php?t=5'>Last Login</a>" . ($type == AchievementType::UNOFFICIAL ? "*" : "") . "</th>";

        $userCount = 0;
        foreach ($devStatsList as $devStats) {
            if ($userCount++ % 2 == 0) {
                echo "<tr>";
            } else {
                echo "<tr class=\"alt\">";
            }

            $dev = $devStats['Author'];
            echo "<td class='text-nowrap'>";
            echo GetUserAndTooltipDiv($dev, true);
            echo "</td>";
            echo "<td class='text-nowrap'><div class='fixheightcell'>";
            echo GetUserAndTooltipDiv($dev, false);
            echo "<br><small>";
            if ($devStats['Permissions'] == Permissions::JuniorDeveloper) {
                echo Permissions::toString(Permissions::JuniorDeveloper);
            } elseif ($devStats['Permissions'] <= Permissions::JuniorDeveloper) {
                echo "Inactive";
            }
            echo "</small>";
            echo "</div></td>";

            echo "<td class='text-right'>" . $devStats['OpenTickets'] . "</td>";
            echo "<td class='text-right'>" . $devStats['Achievements'] . "</td>";
            echo "<td class='text-right'>" . number_format($devStats['TicketRatio'] * 100, 2) . "</td>";
            echo "<td class='text-right'>" . $devStats['ContribCount'] . "</td>";
            echo "<td class='text-right'>" . $devStats['ContribYield'] . "</td>";
            echo "<td class='text-right'>" . $devStats['ActiveClaims'] . "</td>";
            // echo "<td class='text-right smalldate'>" . getNiceDate( strtotime( $devStats[ 'LastLogin' ] ) ) . "</td>";
        }
        echo "</tbody></table></div>";
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
