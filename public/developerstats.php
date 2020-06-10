<?php
require_once __DIR__ . '/../vendor/autoload.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$errorCode = seekGET('e');
$type = seekGET('t', 0);

RenderHtmlStart();
RenderHtmlHead("Developer Stats");
?>
<body>
<?php
RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions);
RenderToolbar($user, $permissions);
?>
<div id='mainpage'>
    <div id='fullcontainer'>
        <h3>Developer Stats</h3>
        <?php
        RenderErrorCodeWarning($errorCode);
        $devStatsList = GetDeveloperStatsFull(100, $type);

        echo "<div class='rightfloat'>* = ordered by</div>";
        echo "<table><tbody>";
        echo "<th></th>";
        echo "<th><a href='/developerstats.php?t=6'>Name</a>" . ($type == 6 ? "*" : "") . "</th>";
        echo "<th class='text-right text-nowrap'><a href='/developerstats.php?t=3'>Open Tickets</a>" . ($type == 3 ? "*" : "") . "</th>";
        echo "<th class='text-right text-nowrap'><a href='/developerstats.php?'>Achievements</a>" . ($type == 0 ? "*" : "") . "</th>";
        echo "<th class='text-right text-nowrap'><a href='/developerstats.php?t=4'>Ticket Ratio (%)</a>" . ($type == 4 ? "*" : "") . "</th>";
        echo "<th class='text-right'><a href='/developerstats.php?t=2' title='Achievements unlocked by others'>Yielded Unlocks</a>" . ($type == 2 ? "*" : "") . "</th>";
        echo "<th class='text-right'><a href='/developerstats.php?t=1' title='Points gained by others through achievement unlocks'>Yielded Points</a>" . ($type == 1 ? "*" : "") . "</th>";
        // echo "<th class='text-right text-nowrap'><a href='/developerstats.php?t=5'>Last Login</a>" . ($type == 5 ? "*" : "") . "</th>";

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
            // echo $devStats['Permissions'] < \RA\Permissions::Developer ? "Inactive" : "Active";
            echo $devStats['Permissions'] < \RA\Permissions::Developer ? "Inactive" : "";
            echo "</small>";
            echo "</div></td>";

            echo "<td class='text-right'>" . $devStats['OpenTickets'] . "</td>";
            echo "<td class='text-right'>" . $devStats['Achievements'] . "</td>";
            echo "<td class='text-right'>" . number_format($devStats['TicketRatio'] * 100, 2) . "</td>";
            echo "<td class='text-right'>" . $devStats['ContribCount'] . "</td>";
            echo "<td class='text-right'>" . $devStats['ContribYield'] . "</td>";
            // echo "<td class='text-right smalldate'>" . getNiceDate( strtotime( $devStats[ 'LastLogin' ] ) ) . "</td>";
        }
        echo "</tbody></table>";
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
