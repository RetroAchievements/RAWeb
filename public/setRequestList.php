<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$maxCount = 50;
$offset = 0;

$username = seekGET('u');
$errorCode = seekGET('e');
$count = seekGET('c', $maxCount);
$offset = seekGET('o', $offset);
$flag = seekGET('f', 0); //0 - display only active user set requests, else display all user set requests
if ($offset < 0) {
    $offset = 0;
}

if ($username === null) {
    $setRequestList = getMostRequestedSetsList($offset, $count);
    $totalRequestedGames = getGamesWithRequests();
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
            //Looking at most requested sets
            echo "<h2 class='longheader'>Most Requested Sets</h2>";

            //Create table headers
            echo "<table><tbody>";
            echo "<th>Game</th>";
            echo "<th>Requests</th>";

            // Loop through each hash and display its information
            foreach ($setRequestList as $request) {
                echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

                echo "<td>";
                echo GetGameAndTooltipDiv($request['GameID'], $request['GameTitle'], $request['GameIcon'], $request['ConsoleName']);
                echo "</td>";
                echo "<td><a href='/setRequestors.php?g=" . $request['GameID'] . "'>" . $request['Requests'] . "</a></td>";
            }
            echo "</tbody></table>";

            //Add page traversal links
            echo "<div class='rightalign row'>";
            if ($offset > 0) {
                $prevOffset = $offset - $maxCount;
                echo "<a href='/setRequestList.php'>First</a> - ";
                echo "<a href='/setRequestList.php?o=$prevOffset'>&lt; Previous $maxCount</a> - ";
            }
            if ($gameCounter == $maxCount && $offset != ($totalRequestedGames - $maxCount)) {
                $nextOffset = $offset + $maxCount;
                echo "<a href='/setRequestList.php?o=$nextOffset'>Next $maxCount &gt;</a>";
                echo " - <a href='/setRequestList.php?o=" . ($totalRequestedGames - $maxCount) . "'>Last</a>";
            }
            echo "</div>";
        } else {
            //Looking at the sets a specific user has requested
            echo "<h2 class='longheader'>$username's Requested Sets - "
                . $userSetRequestInformation['used'] . " of " . $userSetRequestInformation['total'] . " Requests Made</h2>";

            if ($flag == 0) {
                echo "<a href='/setRequestList.php?u=$username&f=1'>View All User Set Requests</a>";
            } else {
                echo "<a href='/setRequestList.php?u=$username'>View Active User Set Requests</a>";
            }
            echo "<br>";
            echo "<br>";

            //Create table headers
            echo "<table><tbody>";
            echo "<th>Game</th>";

            // Loop through each set request and display them if they do not have any acheivements
            foreach ($setRequestList as $request) {
                if ($flag == 0) {
                    if (count(getAchievementIDs($request['GameID'])['AchievementIDs']) == 0) {
                        echo $gameCounter++ % 2 == 0 ? "<tr>" : "<tr class=\"alt\">";

                        echo "<td>";
                        echo GetGameAndTooltipDiv($request['GameID'], $request['GameTitle'], $request['GameIcon'], $request['ConsoleName']);
                        echo "</td>";
                    }
                } else {
                    if (count(getAchievementIDs($request['GameID'])['AchievementIDs']) == 0) {
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
            echo "</tbody></table>";
        }
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
