<?php

use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use Illuminate\Support\Facades\Blade;

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
    if ($dev == null) {
        echo "<th><a href='/gameList.php?s=$sort1&$queryParams'>Title</a></th>";
        echo "<th class='text-right'><a href='/gameList.php?s=$sort2&$queryParams'>Achievements</a></th>";
        echo "<th class='text-right'><a href='/gameList.php?s=$sort3&$queryParams'>Points</a></th>";
        echo "<th class='text-right'><a href='/gameList.php?s=$sort7&$queryParams'>Retro Ratio</a></th>";
        echo "<th class='text-right'><a href='/gameList.php?s=$sort4&$queryParams'>Leaderboards</a></th>";

        if ($showTickets) {
            echo "<th class='whitespace-nowrap text-right'><a href='/gameList.php?s=$sort5&$queryParams'>Open Tickets</a></th>";
        }
    } else {
        echo "<th>Title</th>";
        echo "<th class='text-right'>Achievements</th>";
        echo "<th class='text-right'>Points</th>";
        echo "<th class='text-right'>Retro Ratio</th>";
        echo "<th class='text-right'>Leaderboards</th>";

        if ($showTickets) {
            echo "<th class='whitespace-nowrap text-right'>Open Tickets</th>";
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

        echo "<td class='pr-0 w-full xl:w-auto'>";
        echo gameAvatar($gameEntry, title: $gameEntry['Title'], iconClass: 'mr-2');
        echo "</td>";

        if ($dev == null) {
            echo "<td class='text-right'>$numAchievements</td>";
            echo "<td class='whitespace-nowrap text-right'>" . localized_number($maxPoints); 
            echo Blade::render("<x-points-weighted-container>(" . localized_number($numTrueRatio) . ")</x-points-weighted-container>");
            echo "</td>";
        } else {
            echo "<td class='text-right'>$numAchievements of $totalAchievements</td>";
            echo "<td class='whitespace-nowrap text-right'>$numPoints of $maxPoints <span class='TrueRatio'>($numTrueRatio)</span></td>";
        }

        echo "<td class='text-right'>$retroRatio</td>";

        echo "<td class='text-right'>";
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
            echo "<td class='text-right'>";
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
        echo "<td><b>Totals: " . localized_number($gameCount) . " " . trans_choice(__('resource.game.title'), $gameCount) . "</b></td>";
        echo "<td class='text-right'><b>" . localized_number($achievementsTally) . "</b></td>";
        echo "<td class='text-right'><b>" . localized_number($pointsTally) . "</b>";
        echo Blade::render("<x-points-weighted-container>(" . localized_number($truePointsTally) . ")</x-points-weighted-container>");
        echo "</td>";
        echo "<td></td>";
        echo "<td class='text-right'><b>" . localized_number($lbCount) . "</b></td>";
        if ($showTickets) {
            echo "<td class='text-right'><b>" . localized_number($ticketsCount) . "</b></td>";
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

function renderConsoleHeading(int $consoleID, string $consoleName, bool $isSmall = false): string
{
    $systemIconUrl = getSystemIconUrl($consoleID);
    $iconSize = $isSmall ? 24 : 32;
    $headingSizeClassName = $isSmall ? 'text-h3' : '';

    return <<<HTML
        <h2 class="flex gap-x-2 items-center $headingSizeClassName">
            <img src="$systemIconUrl" alt="Console icon" width="$iconSize" height="$iconSize">
            <span>$consoleName</span>
        </h2>
    HTML;
}
?>
<article>
    <?php
    if ($dev !== null) {
        // Determine which consoles the dev has created content for
        $devConsoles = [];
        foreach ($consoleList as $consoleID => $consoleName) {
            $consoleGames = array_filter($gamesList, fn ($game) => $game['ConsoleID'] == $consoleID);
            if (!empty($consoleGames)) {
                $devConsoles[$consoleName] = ['consoleID' => $consoleID, 'consoleGames' => $consoleGames];
            }
        }

        ksort($devConsoles);

        foreach ($devConsoles as $consoleName => $consoleData) {
            sanitize_outputs($consoleName);

            echo renderConsoleHeading($consoleData['consoleID'], $consoleName, $isSmall = true);
            ListGames($consoleData['consoleGames'], $dev, '', $sortBy, $showTickets, false, true);

            echo "<br/>";
        }
    } else {
        echo renderConsoleHeading($consoleIDInput, $consoleName);

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
            echo "<div class='text-right'>";
            RenderPaginator($gamesCount, $maxCount, $offset, "/gameList.php?s=$sortBy&c=$consoleIDInput&f=$filter&o=");
            echo "</div>";
        }
    }
    ?>
</article>
<?php RenderContentEnd(); ?>
