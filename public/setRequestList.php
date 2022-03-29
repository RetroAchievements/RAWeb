<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    header("Location: " . getenv('APP_URL'));
    exit;
}

$maxCount = 50;
$offset = 0;

$username = requestInputSanitized('u');
$errorCode = requestInputSanitized('e');
$selectedConsole = requestInputSanitized('s', null, 'integer');
$count = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', $offset, 'integer');
$flag = requestInputSanitized('f', 0, 'integer'); // 0 - display only active user set requests, else display all user set requests
if ($offset < 0) {
    $offset = 0;
}

// Get and sort the console list
$consoles = getConsoleIDs();
usort($consoles, fn ($a, $b) => $a['Name'] <=> $b['Name']);

$totalRequestedGames = null;
$userSetRequestInformation = null;
if (empty($username)) {
    if ($selectedConsole == null) {
        $validConsoles = [];
        foreach ($consoles as $console) {
            if (isValidConsoleID($console['ID'])) {
                $validConsoles[] = $console['ID'];
            }
        }
        $setRequestList = getMostRequestedSetsList($validConsoles, $offset, $count);
        $totalRequestedGames = getGamesWithRequests($validConsoles);
    } elseif ($selectedConsole == -1) {
        $setRequestList = getMostRequestedSetsList(null, $offset, $count);
        $totalRequestedGames = getGamesWithRequests(null);
    } else {
        $setRequestList = getMostRequestedSetsList($selectedConsole, $offset, $count);
        $totalRequestedGames = getGamesWithRequests($selectedConsole);
    }
} else {
    $setRequestList = getUserRequestList($username);
    $userSetRequestInformation = getUserRequestsInformation($username, $setRequestList);
}

RenderHtmlStart();
RenderHtmlHead("Set Requests");
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

        $gameCounter = 0;

        if ($username === null) {
            echo "<h2 class='longheader'>";
            if ($selectedConsole > 0) {
                echo "Most Requested " . array_column($consoles, 'Name', 'ID')[$selectedConsole] . " Sets";
            } else {
                echo "Most Requested Sets";
            }
            echo "</h2><div style='float:left'>$totalRequestedGames Requested Sets</div>";

            echo "<div align='right'>";
            echo "Filter by console: ";
            echo "<td><select class='gameselector' onchange='window.location = \"/setRequestList.php\" + this.options[this.selectedIndex].value'>";
            if ($selectedConsole == null) {
                echo "<option selected>-- Supported Systems --</option>";
            } else {
                echo "<option value=''>-- Supported Systems --</option>";
            }
            if ($selectedConsole == -1) {
                echo "<option selected>-- All Systems --</option>";
            } else {
                echo "<option value='?s=-1'>-- All Systems --</option>";
            }

            foreach ($consoles as $console) {
                sanitize_outputs($console['Name']);
                if ($selectedConsole == $console['ID']) {
                    echo "<option selected>" . $console['Name'] . "</option>";
                } else {
                    echo "<option value='?s=" . $console['ID'] . "'>" . $console['Name'] . "</option>";
                    echo "<a href=\"/setRequestList.php\">" . $console['Name'] . "</a><br>";
                }
            }

            echo "</td>";
            echo "</select>";
            echo "</div>";

            // Create table headers
            echo "</br><div class='table-wrapper'><table><tbody>";
            echo "<th>Game</th>";
            echo "<th>Requests</th>";

            // Loop through each set request and display its information
            foreach ($setRequestList as $request) {
                echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

                echo "<td>";
                echo GetGameAndTooltipDiv($request['GameID'], $request['GameTitle'], $request['GameIcon'], $request['ConsoleName']);
                echo "</td>";
                echo "<td><a href='/setRequestors.php?g=" . $request['GameID'] . "'>" . $request['Requests'] . "</a></td>";
            }
            echo "</tbody></table></div>";

            // Add page traversal links
            echo "<div class='rightalign row'>";
            if ($offset > 0) {
                $prevOffset = $offset - $maxCount;
                if (!empty($selectedConsole)) {
                    echo "<a href='/setRequestList.php?s=$selectedConsole'>First</a> - ";
                    echo "<a href='/setRequestList.php?o=$prevOffset&s=$selectedConsole'>&lt; Previous $maxCount</a> - ";
                } else {
                    echo "<a href='/setRequestList.php'>First</a> - ";
                    echo "<a href='/setRequestList.php?o=$prevOffset'>&lt; Previous $maxCount</a> - ";
                }
            }
            if ($gameCounter == $maxCount && $offset != ($totalRequestedGames - $maxCount)) {
                $nextOffset = $offset + $maxCount;
                if (!empty($selectedConsole)) {
                    echo "<a href='/setRequestList.php?o=$nextOffset&s=$selectedConsole'>Next $maxCount &gt;</a>";
                    echo " - <a href='/setRequestList.php?o=" . ($totalRequestedGames - $maxCount) . "&s=$selectedConsole'>Last</a>";
                } else {
                    echo "<a href='/setRequestList.php?o=$nextOffset'>Next $maxCount &gt;</a>";
                    echo " - <a href='/setRequestList.php?o=" . ($totalRequestedGames - $maxCount) . "'>Last</a>";
                }
            }
            echo "</div>";
        } else {
            // Looking at the sets a specific user has requested
            echo "<h2 class='longheader'>$username's Requested Sets - "
                . $userSetRequestInformation['used'] . " of " . $userSetRequestInformation['total'] . " Requests Made</h2>";

            if ($flag == 0) {
                echo "<a href='/setRequestList.php?u=$username&f=1'>View All User Set Requests</a>";
            } else {
                echo "<a href='/setRequestList.php?u=$username'>View Active User Set Requests</a>";
            }
            echo "<br>";
            echo "<br>";

            // Create table headers
            echo "<div class='table-wrapper'><table><tbody>";
            echo "<th>Game</th>";

            // Loop through each set request and display them if they do not have any achievements
            foreach ($setRequestList as $request) {
                if ($flag == 0) {
                    if (empty(getAchievementIDs($request['GameID'])['AchievementIDs'])) {
                        echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

                        echo "<td>";
                        echo GetGameAndTooltipDiv($request['GameID'], $request['GameTitle'], $request['GameIcon'], $request['ConsoleName']);
                        echo "</td>";
                    }
                } else {
                    if (empty(getAchievementIDs($request['GameID'])['AchievementIDs'])) {
                        echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

                        echo "<td>";
                        echo GetGameAndTooltipDiv($request['GameID'], $request['GameTitle'], $request['GameIcon'], $request['ConsoleName']);
                        echo "</td>";
                    } else {
                        echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

                        echo "<td>";
                        echo GetGameAndTooltipDiv($request['GameID'], $request['GameTitle'], $request['GameIcon'], $request['ConsoleName']) . " - Set Exists";
                        echo "</td>";
                    }
                }
            }
            echo "</tbody></table></div>";
        }
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
