<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$consoleList = getConsoleList();
$consoleIDInput = requestInputSanitized('c', 0, 'integer');
$showCompleteGames = requestInputSanitized('f', 0, 'integer'); //	0 = no filter, 1 = only complete, 2 = only incomplete

$sortBy = requestInputSanitized('s', 0, 'integer');
$dev = requestInputSanitized('d');
$filter = requestInputSanitized('f');

if ($dev == null) {
    $maxCount = 50;
    $offset = max(requestInputSanitized('o', 0, 'integer'), 0);
} else {
    $maxCount = 0;
    $offset = 0;
}

$requestedConsole = "";
if ($consoleIDInput !== 0) {
    $requestedConsole = " (" . $consoleList[$consoleIDInput] . ")";
}

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$showTickets = (isset($user) && $permissions >= \RA\Permissions::Developer);
$gamesList = [];
$gamesCount = getGamesListByDev($dev, $consoleIDInput, $gamesList, $sortBy, $showTickets, $filter, $offset, $maxCount);

sanitize_outputs($requestedConsole);

function ListGames($gamesList, $dev, $consoleIDInput, $sortBy, $showTickets)
{
    echo "\n<div class='table-wrapper'><table><tbody>";

    $sort1 = ($sortBy == 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;
    $sort4 = ($sortBy == 4) ? 14 : 4;
    $sort5 = ($sortBy == 5) ? 15 : 5;
    $sort6 = ($sortBy == 6) ? 16 : 6;

    echo "<tr>";
    echo "<th></th>";
    if ($dev == null) {
        echo "<th><a href='/gameList.php?s=$sort1&c=$consoleIDInput'>Title</a></th>";
        echo "<th><a href='/gameList.php?s=$sort2&c=$consoleIDInput'>Achievements</a></th>";
        echo "<th><a href='/gameList.php?s=$sort3&c=$consoleIDInput'>Points</a></th>";
        echo "<th style='white-space: nowrap'><a href='/gameList.php?s=$sort6&c=$consoleIDInput'>Last Updated</a></th>";
        echo "<th><a href='/gameList.php?s=$sort4&c=$consoleIDInput'>Leaderboards</a></th>";

        if ($showTickets) {
            echo "<th class='text-nowrap'><a href='/gameList.php?s=$sort5&c=$consoleIDInput'>Open Tickets</a></th>";
        }
    } else {
        echo "<th>Title</th>";
        echo "<th>Achievements</th>";
        echo "<th>Points</th>";
        echo "<th style='white-space: nowrap'>Last Updated</th>";
        echo "<th>Leaderboards</th>";

        if ($showTickets) {
            echo "<th class='text-nowrap'>Open Tickets</th>";
        }
    }

    echo "</tr>";

    $gameCount = 0;
    $pointsTally = 0;
    $achievementsTally = 0;
    $truePointsTally = 0;
    $lbCount = 0;
    $ticketsCount = null;
    $ticketsCount = 0;

    foreach ($gamesList as $gameEntry) {
        $title = $gameEntry['Title'];
        $gameID = $gameEntry['ID'];
        $maxPoints = $gameEntry['MaxPointsAvailable'] ?? 0;
        $totalTrueRatio = $gameEntry['TotalTruePoints'];
        $totalAchievements = null;
        if ($dev == null) {
            $numAchievements = $gameEntry['NumAchievements'];
        } else {
            $numAchievements = $gameEntry['MyAchievements'];
            $totalAchievements = $numAchievements + $gameEntry['NotMyAchievements'];
        }
        $numLBs = $gameEntry['NumLBs'];
        $gameIcon = $gameEntry['GameIcon'];

        sanitize_outputs($title);

        echo "<tr>";

        echo "<td>";
        echo GetGameAndTooltipDiv($gameID, $title, $gameIcon, null, true);
        echo "</td>";
        echo "<td class='fullwidth'>";
        echo GetGameAndTooltipDiv($gameID, $title, $gameIcon, null, false, null, true);
        echo "</td>";

        if ($dev == null) {
            echo "<td>$numAchievements</td>";
        } else {
            echo "<td>$numAchievements of $totalAchievements</td>";
        }
        echo "<td class='text-nowrap'>$maxPoints <span class='TrueRatio'>($totalTrueRatio)</span></td>";

        if ($gameEntry['DateModified'] != null) {
            $lastUpdated = date("d M, Y", strtotime($gameEntry['DateModified']));
            echo "<td>$lastUpdated</td>";
        } else {
            echo "<td/>";
        }

        echo "<td class=''>";
        if ($numLBs > 0) {
            echo "<a href=\"game/$gameID\">$numLBs</a>";
            $lbCount += $numLBs;
        }
        echo "</td>";

        if ($showTickets) {
            $openTickets = $gameEntry['OpenTickets'];
            echo "<td class=''>";
            if ($openTickets > 0) {
                echo "<a href=\"ticketmanager.php?g=$gameID\">$openTickets</a>";
                $ticketsCount += $openTickets;
            }
            echo "</td>";
        }

        echo "</tr>";

        $gameCount++;
        $pointsTally += $maxPoints;
        $achievementsTally += $numAchievements;
        $truePointsTally += $totalTrueRatio;
    }

    if ($dev != null) {
        // Totals:
        echo "<tr>";
        echo "<td></td>";
        echo "<td><b>Totals: $gameCount games</b></td>";
        echo "<td><b>$achievementsTally</b></td>";
        echo "<td><b>$pointsTally</b><span class='TrueRatio'> ($truePointsTally)</span></td>";
        echo "<td></td>";
        echo "<td><b>$lbCount</b></td>";
        if ($showTickets) {
            echo "<td><b>$ticketsCount</b></td>";
        }
        echo "</tr>";
    }

    echo "</tbody></table></div>";
}

$errorCode = requestInputSanitized('e');
RenderHtmlStart();
RenderHtmlHead("Supported Games" . $requestedConsole);
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <div class="largelist">
            <?php
                if ($dev !== null) {
                    // Output all console lists fetched
                    foreach ($consoleList as $consoleID => $consoleName) {
                        $consoleGames = array_filter($gamesList, function ($game) use ($consoleID) {
                            return $game['ConsoleID'] == $consoleID;
                        });
                        if (empty($consoleGames)) {
                            continue;
                        }

                        sanitize_outputs($consoleName);
                        echo "<h2 class='longheader'>$consoleName</h2>";

                        listGames($consoleGames, $dev, $consoleID, $sortBy, $showTickets);

                        echo "<br/>";
                    }
                } else {
                    $consoleName = $consoleList[$consoleIDInput];
                    sanitize_outputs($consoleName);
                    echo "<h2 class='longheader'>$consoleName</h2>";

                    echo "<div style='float:left'>$gamesCount Games</div>";

                    echo "<div align='right'>";
                    echo "<select class='gameselector' onchange='window.location = \"/gameList.php?s=$sortBy&c=$consoleIDInput\" + this.options[this.selectedIndex].value'>";
                    echo "<option value=''" . (($filter == 0) ? " selected" : "") . ">Games with achievements</option>";
                    echo "<option value='&f=1'" . (($filter == 1) ? " selected" : "") . ">Games without achievements</option>";
                    echo "<option value='&f=2'" . (($filter == 2) ? " selected" : "") . ">All games</option>";
                    echo "</select>";
                    echo "</div>";

                    echo "<br/>";
                    listGames($gamesList, null, $consoleIDInput, $sortBy, $showTickets);

                    // Add page traversal links
                    echo "\n<div class='rightalign row'>";
                    if ($offset > 0) {
                        $prevOffset = $offset - $maxCount;
                        echo "<a href='/gameList.php?s=$sortBy&c=$consoleIDInput&f=$filter'>First</a> - ";
                        echo "<a href='/gameList.php?s=$sortBy&c=$consoleIDInput&f=$filter&o=$prevOffset'>&lt; Previous $maxCount</a> - ";
                    }
                    $nextOffset = $offset + $maxCount;
                    if ($nextOffset < $gamesCount) {
                        $lastOffset = $gamesCount - ($gamesCount % $maxCount);
                        echo "<a href='/gameList.php?s=$sortBy&c=$consoleIDInput&f=$filter&o=$nextOffset'>Next $maxCount &gt;</a> - ";
                        echo "<a href='/gameList.php?s=$sortBy&c=$consoleIDInput&f=$filter&o=$lastOffset'>Last</a>";
                    }
                    echo "</div>";
                }
            ?>
            <br>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
