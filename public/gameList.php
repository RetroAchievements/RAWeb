<?php

use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Enums\Permissions;

$consoleList = System::get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name']);
$consoleIDInput = requestInputSanitized('c', 0, 'integer');
$filter = requestInputSanitized('f', 0, 'integer'); // 0 = no filter, 1 = only complete, 2 = only incomplete
$sortBy = requestInputSanitized('s', 0, 'integer');
$dev = requestInputSanitized('d');

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
$gamesCount = getGamesListByDev($dev, $consoleIDInput, $gamesList, (int) $sortBy, $showTickets, $filter, $offset, $maxCount);

function ListGames(
    array $gamesList,
    ?string $dev = null,
    string $queryParams = '',
    int $sortBy = 0,
    bool $showTickets = false,
    bool $showConsoleName = false,
    bool $showTotals = false,
): void {
    echo "\n<div class='table-wrapper'><table class='table-highlight'><tbody>";

    $sort1 = ($sortBy <= 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;
    $sort4 = ($sortBy == 4) ? 14 : 4;
    $sort5 = ($sortBy == 5) ? 15 : 5;
    $sort6 = ($sortBy == 6) ? 16 : 6;
    $sort7 = ($sortBy == 7) ? 17 : 7;

    echo "<tr class='do-not-highlight'>";
    echo "<th class='pr-0'></th>";
    if ($dev == null) {
        echo "<th><a href='/gameList.php?s=$sort1&$queryParams'>Title</a></th>";
        echo "<th><a href='/gameList.php?s=$sort2&$queryParams'>Achievements</a></th>";
        echo "<th><a href='/gameList.php?s=$sort3&$queryParams'>Points</a></th>";
        echo "<th><a href='/gameList.php?s=$sort7&$queryParams'>Retro Ratio</a></th>";
        echo "<th style='white-space: nowrap'><a href='/gameList.php?s=$sort6&$queryParams'>Last Updated</a></th>";
        echo "<th><a href='/gameList.php?s=$sort4&$queryParams'>Leaderboards</a></th>";

        if ($showTickets) {
            echo "<th class='whitespace-nowrap'><a href='/gameList.php?s=$sort5&$queryParams'>Open Tickets</a></th>";
        }
    } else {
        echo "<th>Title</th>";
        echo "<th>Achievements</th>";
        echo "<th>Points</th>";
        echo "<th>Retro Ratio</th>";
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
    $ticketsCount = 0;

    foreach ($gamesList as $gameEntry) {
        $title = $gameEntry['Title'];
        $gameID = $gameEntry['ID'];
        $maxPoints = $gameEntry['MaxPointsAvailable'] ?? 0;
        $totalTrueRatio = $gameEntry['TotalTruePoints'];
        $retroRatio = $gameEntry['RetroRatio'];
        $totalAchievements = null;
        $devLeaderboards = null;
        $devTickets = null;
        if ($dev == null) {
            $numAchievements = $gameEntry['NumAchievements'];
            $numPoints = $maxPoints;
            $numTrueRatio = $totalTrueRatio;
        } else {
            $numAchievements = $gameEntry['MyAchievements'];
            $numPoints = $gameEntry['MyPoints'];
            $numTrueRatio = $gameEntry['MyTrueRatio'];
            $totalAchievements = $numAchievements + $gameEntry['NotMyAchievements'];
            $devLeaderboards = $gameEntry['MyLBs'];
            $devTickets = $showTickets == true ? $gameEntry['MyOpenTickets'] : null;
        }
        $numLBs = $gameEntry['NumLBs'];

        sanitize_outputs($title);

        echo "<tr>";

        echo "<td class='pr-0'>";
        echo gameAvatar($gameEntry, label: false);
        echo "</td>";
        echo "<td class='w-full'>";
        $gameLabelData = $gameEntry;
        unset($gameLabelData['ConsoleName']);
        echo gameAvatar($gameLabelData, icon: false);
        echo "</td>";

        if ($dev == null) {
            echo "<td>$numAchievements</td>";
            echo "<td class='whitespace-nowrap'>$maxPoints <span class='TrueRatio'>($numTrueRatio)</span></td>";
        } else {
            echo "<td>$numAchievements of $totalAchievements</td>";
            echo "<td class='whitespace-nowrap'>$numPoints of $maxPoints <span class='TrueRatio'>($numTrueRatio)</span></td>";
        }

        echo "<td>$retroRatio</td>";

        if ($gameEntry['DateModified'] != null) {
            $lastUpdated = date("d M, Y", strtotime($gameEntry['DateModified']));
            echo "<td>$lastUpdated</td>";
        } else {
            echo "<td/>";
        }

        echo "<td class=''>";
        if ($numLBs > 0) {
            if ($dev == null) {
                echo "<a href=\"game/$gameID\">$numLBs</a>";
                $lbCount += $numLBs;
            } else {
                echo "<a href=\"game/$gameID\">$devLeaderboards of $numLBs</a>";
                $lbCount += $devLeaderboards;
            }
        }
        echo "</td>";

        if ($showTickets) {
            $openTickets = $gameEntry['OpenTickets'];
            echo "<td class=''>";
            if ($openTickets > 0) {
                if ($dev == null) {
                    echo "<a href='ticketmanager.php?g=$gameID'>$openTickets</a>";
                    $ticketsCount += $openTickets;
                } else {
                    echo "<a href='ticketmanager.php?g=$gameID'>$devTickets of $openTickets</a>";
                    $ticketsCount += $devTickets;
                }
            }
            echo "</td>";
        }

        echo "</tr>";

        $gameCount++;
        $pointsTally += $numPoints;
        $achievementsTally += $numAchievements;
        $truePointsTally += $numTrueRatio;
    }

    if ($showTotals) {
        // Totals:
        echo "<tr class='do-not-highlight'>";
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

if ($consoleList->has($consoleIDInput)) {
    $consoleName = $consoleList[$consoleIDInput];
    $requestedConsole = $consoleName;
} elseif ($consoleIDInput === 0) {
    $consoleName = "All Games";
    $requestedConsole = "All";
} else {
    abort(404);
}
sanitize_outputs($consoleName, $requestedConsole);

RenderContentStart($requestedConsole . " Games");
?>
<div id="mainpage">
    <div id="fullcontainer">
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
                $fallBackConsoleIcon = asset("assets/images/system/unknown.png");
                $cleanSystemShortName = Str::lower(str_replace("/", "", config("systems.$consoleIDInput.name_short")));
                $iconName = Str::kebab($cleanSystemShortName);

                echo "<h2 class='flex gap-x-2'>";
                echo " <img src='" . asset("assets/images/system/$iconName.png") . "' alt='' width='32' height='32'";
                echo " onerror='this.src=\"$fallBackConsoleIcon\"'></img>"; // fallback
                echo " <span>$consoleName</span>";
                echo "</h2>";

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
