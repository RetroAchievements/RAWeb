<?php

use RA\Permissions;

$consoleList = getConsoleList();
$consoleIDInput = requestInputSanitized('c', 0, 'integer');
$showCompleteGames = requestInputSanitized('f', 0, 'integer'); // 0 = no filter, 1 = only complete, 2 = only incomplete

$sortBy = requestInputSanitized('s', 0, 'integer');
$dev = requestInputSanitized('d');
$filter = requestInputSanitized('f');

if ($dev == null && ($consoleIDInput == 0 || $filter != 0)) {
    $maxCount = 50;
    $offset = max(requestInputSanitized('o', 0, 'integer'), 0);
} else {
    $maxCount = 0;
    $offset = 0;
}

authenticateFromCookie($user, $permissions, $userDetails);

$showTickets = (isset($user) && $permissions >= Permissions::Developer);
$gamesList = [];
$gamesCount = getGamesListByDev($dev, $consoleIDInput, $gamesList, $sortBy, $showTickets, $filter, $offset, $maxCount);

function ListGames($gamesList, $dev, $queryParams, $sortBy, $showTickets, $showConsoleName, $showTotals): void
{
    echo "\n<div class='table-wrapper'><table><tbody>";

    $sort1 = ($sortBy <= 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;
    $sort4 = ($sortBy == 4) ? 14 : 4;
    $sort5 = ($sortBy == 5) ? 15 : 5;
    $sort6 = ($sortBy == 6) ? 16 : 6;

    echo "<tr>";
    echo "<th></th>";
    if ($dev == null) {
        echo "<th><a href='/gameList.php?s=$sort1&$queryParams'>Title</a></th>";
        echo "<th><a href='/gameList.php?s=$sort2&$queryParams'>Achievements</a></th>";
        echo "<th><a href='/gameList.php?s=$sort3&$queryParams'>Points</a></th>";
        echo "<th style='white-space: nowrap'><a href='/gameList.php?s=$sort6&$queryParams'>Last Updated</a></th>";
        echo "<th><a href='/gameList.php?s=$sort4&$queryParams'>Leaderboards</a></th>";

        if ($showTickets) {
            echo "<th class='whitespace-nowrap'><a href='/gameList.php?s=$sort5&$queryParams'>Open Tickets</a></th>";
        }
    } else {
        echo "<th>Title</th>";
        echo "<th>Achievements</th>";
        echo "<th>Points</th>";
        echo "<th style='white-space: nowrap'>Last Updated</th>";
        echo "<th>Leaderboards</th>";

        if ($showTickets) {
            echo "<th class='whitespace-nowrap'>Open Tickets</th>";
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

        $consoleName = $showConsoleName ? $gameEntry['ConsoleName'] : null;

        sanitize_outputs($title);

        echo "<tr>";

        echo "<td>";
        echo GetGameAndTooltipDiv($gameID, $title, $gameIcon, $consoleName, true);
        echo "</td>";
        echo "<td class='w-full'>";
        echo GetGameAndTooltipDiv($gameID, $title, $gameIcon, $consoleName, false, null, true);
        echo "</td>";

        if ($dev == null) {
            echo "<td>$numAchievements</td>";
        } else {
            echo "<td>$numAchievements of $totalAchievements</td>";
        }
        echo "<td class='whitespace-nowrap'>$maxPoints <span class='TrueRatio'>($totalTrueRatio)</span></td>";

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

    if ($showTotals) {
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

$requestedConsole = "";
if ($consoleIDInput !== 0) {
    $requestedConsole = $consoleList[$consoleIDInput] . " ";
}
sanitize_outputs($requestedConsole);

RenderContentStart($requestedConsole . "Games");
?>
<div id="mainpage">
    <div id="fullcontainer">
        <?php RenderConsoleMessage((int) $consoleIDInput) ?>
        <div>
            <?php
                if ($dev !== null) {
                    // Determine which consoles the dev has created content for
                    $devConsoles = [];
                    foreach ($consoleList as $consoleID => $consoleName) {
                        $consoleGames = array_filter($gamesList, fn ($game) => $game['ConsoleID'] == $consoleID);
                        if (!empty($consoleGames)) {
                            $devConsoles[$consoleName] = $consoleGames;
                        }
                    }

                    ksort($devConsoles);

                    foreach ($devConsoles as $consoleName => $consoleGames) {
                        sanitize_outputs($consoleName);
                        echo "<h2>$consoleName</h2>";

                        ListGames($consoleGames, $dev, '', $sortBy, $showTickets, false, true);

                        echo "<br/>";
                    }
                } else {
                    if ($consoleIDInput == 0) {
                        $consoleName = "All Games";
                    } else {
                        $consoleName = $consoleList[$consoleIDInput];
                        sanitize_outputs($consoleName);
                    }
                    echo "<h2>$consoleName</h2>";

                    echo "<div style='float:left'>$gamesCount Games</div>";

                    echo "<div align='right'>";
                    echo "<select class='gameselector' onchange='window.location = \"/gameList.php?s=$sortBy&c=$consoleIDInput\" + this.options[this.selectedIndex].value'>";
                    echo "<option value=''" . (($filter == 0) ? " selected" : "") . ">Games with achievements</option>";
                    echo "<option value='&f=1'" . (($filter == 1) ? " selected" : "") . ">Games without achievements</option>";
                    echo "<option value='&f=2'" . (($filter == 2) ? " selected" : "") . ">All games</option>";
                    echo "</select>";
                    echo "</div>";

                    echo "<br/>";

                    $queryParams = "c=$consoleIDInput&f=$filter";
                    ListGames($gamesList, null, $queryParams, $sortBy, $showTickets, $consoleIDInput == 0, $maxCount == 0);

                    if ($maxCount != 0 && $gamesCount > $maxCount) {
                        // Add page traversal links
                        echo "\n<br/><div class='float-right row'>";
                        RenderPaginator($gamesCount, $maxCount, $offset, "/gameList.php?s=$sortBy&c=$consoleIDInput&f=$filter&o=");
                        echo "</div>";
                    }
                }
            ?>
            <br>
        </div>
    </div>
</div>
<?php RenderContentEnd(); ?>
