<?php

use App\Community\Enums\UserGameListType;
use App\Enums\Permissions;
use App\Platform\Models\System;
use Illuminate\Support\Facades\Blade;

$consoleList = System::get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name']);
$consoleIDInput = requestInputSanitized('c', 0, 'integer');
$filter = requestInputSanitized('f', 0, 'integer'); // 0 = with achievements, 1 = without achievements, 2 = all
$sortBy = requestInputSanitized('s', 1, 'integer');

$dev = requestInputSanitized('d');
if ($dev !== null) {
    return redirect()->route('developer.sets', $dev);
}

if ($consoleIDInput == 0 || $filter != 0) {
    $maxCount = 50;
    $offset = max(requestInputSanitized('o', 0, 'integer'), 0);
} else {
    $maxCount = 0;
    $offset = 0;
}

if ($consoleIDInput > 0 && System::isGameSystem($consoleIDInput)) {
    return redirect()->route('system.game.index', ['system' => $consoleIDInput]);
}

authenticateFromCookie($user, $permissions, $userDetails);

$listType = isset($user) ? requestInputSanitized('t') : null;
$showTickets = (isset($user) && $permissions >= Permissions::Developer);
$gamesList = [];
$gamesCount = getGamesListByDev(null, $consoleIDInput, $gamesList,
    listType: $listType, sortBy: $sortBy,
    ticketsFlag: $showTickets, filter: $filter,
    offset: $offset, count: $maxCount);

function ListGames(
    array $gamesList,
    string $queryParams = '',
    int $sortBy = 0,
    bool $showTickets = false,
    bool $showConsoleName = false,
    bool $showTotals = false,
    bool $showClaims = false,
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
    echo "<th><a href='/gameList.php?s=$sort1$queryParams'>Title</a></th>";
    echo "<th class='text-right'><a href='/gameList.php?s=$sort2$queryParams'>Achievements</a></th>";
    echo "<th class='text-right'><a href='/gameList.php?s=$sort3$queryParams'>Points</a></th>";
    echo "<th class='text-right'><a href='/gameList.php?s=$sort7$queryParams'>Retro Ratio</a></th>";
    echo "<th style='white-space: nowrap' class='text-right'><a href='/gameList.php?s=$sort6$queryParams'>Last Updated</a></th>";
    echo "<th class='text-right'><a href='/gameList.php?s=$sort4$queryParams'>Leaderboards</a></th>";

    if ($showTickets) {
        echo "<th class='whitespace-nowrap text-right'><a href='/gameList.php?s=$sort5&$queryParams'>Open Tickets</a></th>";
    }

    if ($showClaims) {
        echo "<th class='whitespace-nowrap'>Claimed By</th>";
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
        $numAchievements = $gameEntry['NumAchievements'];
        $numPoints = $maxPoints;
        $numTrueRatio = $totalTrueRatio;
        $numLBs = $gameEntry['NumLBs'];

        sanitize_outputs($title);

        echo "<tr>";

        if ($showConsoleName) {
            echo "<td class='pr-0 py-2 w-full xl:w-auto'>";
        } else {
            echo "<td class='pr-0 w-full xl:w-auto'>";
        }
        echo Blade::render('
            <x-game.multiline-avatar
                :gameId="$gameId"
                :gameTitle="$gameTitle"
                :gameImageIcon="$gameImageIcon"
                :consoleName="$consoleName"
            />
        ', [
            'gameId' => $gameEntry['ID'],
            'gameTitle' => $gameEntry['Title'],
            'gameImageIcon' => $gameEntry['GameIcon'],
            'consoleName' => $showConsoleName ? $gameEntry['ConsoleName'] : null,
        ]);
        echo "</td>";

        echo "<td class='text-right'>$numAchievements</td>";
        echo "<td class='whitespace-nowrap text-right'>" . localized_number($maxPoints);
        echo Blade::render("<x-points-weighted-container>(" . localized_number($numTrueRatio) . ")</x-points-weighted-container>");
        echo "</td>";
        echo "<td class='text-right'>$retroRatio</td>";

        if ($gameEntry['DateModified'] != null) {
            $lastUpdated = date("d M, Y", strtotime($gameEntry['DateModified']));
            echo "<td class='text-right'>$lastUpdated</td>";
        } else {
            echo "<td/>";
        }

        echo "<td class='text-right'>";
        if ($numLBs > 0) {
            echo "<a href=\"game/$gameID\">$numLBs</a>";
            $lbCount += $numLBs;
        }
        echo "</td>";

        if ($showTickets) {
            $openTickets = $gameEntry['OpenTickets'];
            echo "<td class='text-right'>";
            if ($openTickets > 0) {
                echo "<a href='ticketmanager.php?g=$gameID'>$openTickets</a>";
                $ticketsCount += $openTickets;
            }
            echo "</td>";
        }

        if ($showClaims) {
            echo "<td>";
            if (array_key_exists('ClaimedBy', $gameEntry)) {
                foreach ($gameEntry['ClaimedBy'] as $claimUser) {
                    echo userAvatar($claimUser);
                    echo "</br>";
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
        echo "<td></td>";
        echo "<td class='text-right'><b>" . localized_number($lbCount) . "</b></td>";
        if ($showTickets) {
            echo "<td class='text-right'><b>" . localized_number($ticketsCount) . "</b></td>";
        }
        echo "</tr>";
    }

    echo "</tbody></table></div>";
}

$combiningConsoleName = '';
if ($consoleList->has($consoleIDInput)) {
    $consoleName = $consoleList[$consoleIDInput];
    $requestedConsole = $consoleName;
    $combiningConsoleName = " $consoleName";
} elseif ($consoleIDInput === 0) {
    $consoleName = "All Games";
    $requestedConsole = "All";
} else {
    abort(404);
}

if ($listType === UserGameListType::Play) {
    $requestedConsole = "Want to Play$combiningConsoleName";
    $consoleName = $requestedConsole . " Games";

    $maxCount = 50;
    $offset = max(requestInputSanitized('o', 0, 'integer'), 0);
} elseif ($listType === UserGameListType::Develop) {
    if ($permissions < Permissions::Developer) {
        abort(403);
    }
    $requestedConsole = "Want to Develop$combiningConsoleName";
    $consoleName = $requestedConsole . " Games";

    $maxCount = 50;
    $offset = max(requestInputSanitized('o', 0, 'integer'), 0);
}

$showClaims = false;
if ($filter !== 0) { // if not viewing "games with achievements", fetch claim info
    $showClaims = true;

    $gameIDs = [];
    foreach ($gamesList as $game) {
        $gameIDs[] = $game['ID'];
    }

    $claimData = getClaimData($gameIDs, false);
    $claimUsers = [];
    foreach ($claimData as $claim) {
        $claimUsers[$claim['GameID']][] = $claim['User'];
    }

    foreach ($gamesList as &$game) {
        $game['ClaimedBy'] = $claimUsers[$game['ID']] ?? [];
    }
}

sanitize_outputs($consoleName, $requestedConsole, $listType);

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
        echo renderConsoleHeading($consoleIDInput, $consoleName);

        echo "<div style='float:left'>$gamesCount " . trans_choice(__('resource.game.title'), $gamesCount) . "</div>";

        $queryParamArray = [];
        if ($listType !== null) {
            $queryParamArray[] = "t=$listType";
        }
        if ($consoleIDInput !== 0) {
            $queryParamArray[] = "c=$consoleIDInput";
        }
        $queryParams = join('&', $queryParamArray);
        if ($queryParams === '') {
            $queryParams = 's=0'; // prevent "gameList.php?&f=X"
        }

        echo "<div align='right'>";
        echo "<select class='gameselector' onchange='window.location = \"/gameList.php?$queryParams\" + this.options[this.selectedIndex].value'>";
        echo "<option value=''" . (($filter == 0) ? " selected" : "") . ">Games with achievements</option>";
        echo "<option value='&f=1'" . (($filter == 1) ? " selected" : "") . ">Games without achievements</option>";
        echo "<option value='&f=2'" . (($filter == 2) ? " selected" : "") . ">All games</option>";
        echo "</select>";
        echo "</div>";

        echo "<br/>";

        if ($filter !== 0) {
            $queryParamArray[] = "f=$filter";
        }
        $queryParams = join('&', $queryParamArray);
        $appendQueryParams = '';
        if ($queryParams !== '') {
            $appendQueryParams = '&' . $queryParams;
        }

        ListGames($gamesList, $appendQueryParams, $sortBy, $showTickets,
                  showConsoleName: ($consoleIDInput == 0), // only show console name if viewing all consoles
                  showTotals: ($maxCount == 0),            // don't show totals if paginating
                  showClaims: $showClaims,
                 );

        if ($maxCount != 0 && $gamesCount > $maxCount) {
            if ($sortBy != 0) {
                $queryParamArray[] = "s=$sortBy";
                $queryParams = join('&', $queryParamArray);
            }

            // Add page traversal links
            echo "<div class='text-right'>";
            RenderPaginator($gamesCount, $maxCount, $offset, "/gameList.php?$queryParams&o=");
            echo "</div>";
        }
    ?>
</article>
<?php RenderContentEnd(); ?>
