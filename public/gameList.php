<?php

use App\Community\Enums\UserGameListType;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;

$consoleList = System::get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name']);
$consoleIDInput = requestInputSanitized('c', 0, 'integer');
$filter = requestInputSanitized('f', 0, 'integer'); // 0 = with achievements, 1 = without achievements, 2 = all
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

$listType = (isset($user) && !isset($dev)) ? requestInputSanitized('t') : null;
$showTickets = (isset($user) && $permissions >= Permissions::Developer);
$gamesList = [];
$gamesCount = getGamesListByDev($dev, $consoleIDInput, $gamesList,
    listType: $listType, sortBy: $sortBy,
    ticketsFlag: $showTickets, filter: $filter,
    offset: $offset, count: $maxCount);

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

        ListGames($gamesList, null, $appendQueryParams, $sortBy, $showTickets,
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
    }
    ?>
</article>
<?php RenderContentEnd(); ?>
