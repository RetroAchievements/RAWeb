<?php

use LegacyApp\Platform\Models\System;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$maxCount = 50;
$offset = 0;
$unclaimedOnly = false;

$username = requestInputSanitized('u');
$selectedConsoleId = (int) request()->input('s');
$count = (int) request()->input('c', $maxCount);
$offset = (int) request()->input('o', $offset);
$unclaimedOnly = (bool) request()->input('x', $unclaimedOnly);
$flag = (int) request()->input('f', 0); // 0 - display only active user set requests, else display all user set requests
if ($offset < 0) {
    $offset = 0;
}

$consoles = System::orderBy('Name')->get(['ID', 'Name']);

/** @var ?System $selectedConsole */
$selectedConsole = $consoles->firstWhere('ID', $selectedConsoleId);

$totalRequestedGames = null;
$userSetRequestInformation = null;
if (empty($username)) {
    if ($selectedConsoleId == null) {
        $validConsoles = [];
        foreach ($consoles as $console) {
            if (isValidConsoleID($console['ID'])) {
                $validConsoles[] = $console['ID'];
            }
        }
        $setRequestList = getMostRequestedSetsList($validConsoles, $offset, $count, $unclaimedOnly);
        $totalRequestedGames = getGamesWithRequests($validConsoles);
    } elseif ($selectedConsoleId == -1) {
        $setRequestList = getMostRequestedSetsList(null, $offset, $count, $unclaimedOnly);
        $totalRequestedGames = getGamesWithRequests(null);
    } else {
        $setRequestList = getMostRequestedSetsList($selectedConsoleId, $offset, $count, $unclaimedOnly);
        $totalRequestedGames = getGamesWithRequests($selectedConsoleId);
    }
} else {
    $setRequestList = getUserRequestList($username);
    $userSetRequestInformation = getUserRequestsInformation($username, $setRequestList);
}

RenderContentStart("Set Requests");
?>
<script>
/**
 * Updates a query parameter in the current URL and navigates to the new URL.
 *
 * @param {string} paramName - The name of the query parameter to update.
 * @param {string} newQueryParamValue - The new value for the query parameter.
 */
function updateUrlParameter(paramName, newQueryParamValue) {
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);

    params.set(paramName, newQueryParamValue);
    url.search = params.toString();

    window.location.href = url.toString();
}

/**
 * Event handler for 'Show only unclaimed games' checkbox change event.
 * Updates 'x' query parameter in the URL based on checkbox state.
 *
 * @param {Event} event - The checkbox change event.
 */
function handleShowOnlyUnclaimedGamesChanged(event) {
    const newQueryParamValue = event.target.checked ? '1' : '0';
    updateUrlParameter('x', newQueryParamValue);
}

/**
 * Event handler for 'Filter by console' selection change event.
 * Updates 's' query parameter in the URL based on selected option.
 *
 * @param {Event} event - The select change event.
 */
function handleConsoleChanged(event) {
    const newQueryParamValue = event.target.value;
    updateUrlParameter('s', newQueryParamValue);
}
</script>

<div id='mainpage'>
    <div id='fullcontainer'>
        <?php
        $gameCounter = 0;

        if ($username === null) {
            echo "<h2>";
            if ($selectedConsoleId > 0) {
                echo "Most Requested " . $selectedConsole->Name . " Sets";
            } else {
                echo "Most Requested Sets";
            }
            echo "</h2>";

            echo "<div class='w-full flex flex-col sm:flex-row justify-between gap-2'>$totalRequestedGames Requested Sets";
            echo "<div class='flex flex-col sm:items-end gap-y-2'>";

            echo "<div>";
            echo "Filter by console: ";
            echo "<select class='gameselector' onchange='handleConsoleChanged(event)'>";
            if ($selectedConsoleId == null) {
                echo "<option selected>-- Supported Systems --</option>";
            } else {
                echo "<option value=''>-- Supported Systems --</option>";
            }
            if ($selectedConsoleId == -1) {
                echo "<option selected>-- All Systems --</option>";
            } else {
                echo "<option value='-1'>-- All Systems --</option>";
            }

            /** @var System $console */
            foreach ($consoles as $console) {
                $consoleName = $console->Name;
                sanitize_outputs($consoleName);
                if ($selectedConsoleId == $console['ID']) {
                    echo "<option selected>" . $consoleName . "</option>";
                } else {
                    echo "<option value='" . $console['ID'] . "'>" . $consoleName . "</option>";
                    echo "<a href=\"/setRequestList.php\">" . $consoleName . "</a><br>";
                }
            }
            echo "</select>";
            echo "</div>";

            $checkedAttribute = $unclaimedOnly ? "checked" : "";
            echo <<<HTML
                <label>
                    <input type="checkbox" $checkedAttribute onchange='handleShowOnlyUnclaimedGamesChanged(event)'>
                    Show only unclaimed games
                </label>
            HTML;

            echo "</div>";
            echo "</div>";

            echo "</br><div class='table-wrapper'><table class='table-highlight'><tbody>";

            // Create table headers
            echo "<tr class='do-not-highlight'>";
            echo "<th>Game</th>";
            echo "<th>Claimed By</th>";
            echo "<th>Requests</th>";
            echo "</tr>";

            // Loop through each set request and display its information
            foreach ($setRequestList as $request) {
                echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

                echo "<td>";
                echo gameAvatar($request);
                echo "</td><td>";
                $claims = explode(',', $request['Claims']);
                foreach ($claims as $devClaim) {
                    echo userAvatar($devClaim);
                    echo "</br>";
                }
                echo "</td>";
                echo "<td><a href='/setRequestors.php?g=" . $request['GameID'] . "'>" . $request['Requests'] . "</a></td>";
            }
            echo "</tbody></table></div>";

            // Add page traversal links
            echo "<div class='float-right row'>";
            $unclaimedOnlyParam = isset($unclaimedOnly) ? "&x=$unclaimedOnly" : "";
            if ($offset > 0) {
                $prevOffset = $offset - $maxCount;
                if (!empty($selectedConsoleId)) {
                    echo "<a href='/setRequestList.php?s=$selectedConsoleId$unclaimedOnlyParam'>First</a> - ";
                    echo "<a href='/setRequestList.php?o=$prevOffset&s=$selectedConsoleId$unclaimedOnlyParam'>&lt; Previous $maxCount</a> - ";
                } else {
                    echo "<a href='/setRequestList.php?$unclaimedOnlyParam'>First</a> - ";
                    echo "<a href='/setRequestList.php?o=$prevOffset$unclaimedOnlyParam'>&lt; Previous $maxCount</a> - ";
                }
            }
            if ($gameCounter == $maxCount && $offset != $totalRequestedGames - $maxCount) {
                $nextOffset = $offset + $maxCount;
                if (!empty($selectedConsoleId)) {
                    echo "<a href='/setRequestList.php?o=$nextOffset&s=$selectedConsoleId$unclaimedOnlyParam'>Next $maxCount &gt;</a>";
                    echo " - <a href='/setRequestList.php?o=" . ($totalRequestedGames - $maxCount) . "&s=$selectedConsoleId$unclaimedOnlyParam'>Last</a>";
                } else {
                    echo "<a href='/setRequestList.php?o=$nextOffset$unclaimedOnlyParam'>Next $maxCount &gt;</a>";
                    echo " - <a href='/setRequestList.php?o=" . ($totalRequestedGames - $maxCount) . "$unclaimedOnlyParam'>Last</a>";
                }
            }
            echo "</div>";
        } else {
            // Looking at the sets a specific user has requested
            echo "<h2>$username's Requested Sets - "
                . $userSetRequestInformation['used'] . " of " . $userSetRequestInformation['total'] . " Requests Made</h2>";

            if ($flag == 0) {
                if ($username === $user) {
                    $softcoreLimit = ($userSetRequestInformation['maxSoftcoreReached']) ? ' (maximum softcore contribution reached)' : '';
                    echo "<div class='float-right'>Next request in " . $userSetRequestInformation['pointsForNext'] . " points$softcoreLimit</div>";
                }
                echo "<a href='/setRequestList.php?u=$username&f=1'>View All User Set Requests</a>";
            } else {
                echo "<a href='/setRequestList.php?u=$username'>View Active User Set Requests</a>";
            }
            echo "<br>";
            echo "<br>";

            echo "<div class='table-wrapper'><table class='table-highlight'><tbody>";

            // Create table headers
            echo "<tr class='do-not-highlight'>";
            echo "<th>Game</th>";
            echo "<th>Claimed By</th>";
            echo "</tr>";

            // Loop through each set request and display them if they do not have any achievements
            foreach ($setRequestList as $request) {
                $setExists = $request['AchievementCount'] > 0;
                if ($flag == 0 && $setExists) {
                    continue;
                }

                echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

                echo "<td>";
                echo gameAvatar($request);
                echo "</td>";

                echo "<td>";
                if (!$setExists) {
                    $claims = explode(',', $request['Claims']);
                    foreach ($claims as $devClaim) {
                        echo userAvatar($devClaim);
                        echo "</br>";
                    }
                } else {
                    echo "Set Exists";
                }
                echo "</td>";
            }
            echo "</tbody></table></div>";
        }
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
