<?php
require_once __DIR__ . '/../vendor/autoload.php';

$consoleList = getConsoleList();
$consoleIDInput = seekGET('c', 0);
settype($consoleIDInput, 'integer');
$showCompleteGames = seekGET('f', 0); //	0 = no filter, 1 = only complete, 2 = only incomplete
settype($showCompleteGames, 'integer');

$sortBy = seekGET('s', 0);
$dev = seekGET('d');

$requestedConsole = "";
if ($consoleIDInput !== 0) {
    $requestedConsole = " " . $consoleList[$consoleIDInput];
}

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$showTickets = (isset($user) && $permissions >= \RA\Permissions::Developer);
$gamesList = [];
if ($showTickets) {
    $gamesCount = getGamesListByDev($dev, $consoleIDInput, $gamesList, $sortBy, true);
} else {
    $gamesCount = getGamesListByDev($dev, $consoleIDInput, $gamesList, $sortBy, false);
}

$errorCode = seekGET('e');
RenderHtmlStart();
RenderHtmlHead("Supported Games" . $requestedConsole);
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="fullcontainer">
        <div class="navpath">
            <?php
            if ($dev != null) {
                echo "<b><a href='/userList.php'>All Users</a> &raquo; <a href='/User/$dev'>$dev</a> &raquo; Achievement Sets</b>";
            } else {
                if ($requestedConsole == "") {
                    echo "<b>All Games</b>";
                } else {
                    echo "<a href=\"/gameList.php\">All Games</a>";
                    echo " &raquo; <b>$requestedConsole</b></a>";
                }
            }
            ?>
        </div>
        <?php if ($user !== null): ?>
            <div class="d-flex flex-wrap justify-content-between">
                <div>
                </div>
                <div>
                    Filter by developer:<br>
                    <form action="/gameList.php">
                        <input type="hidden" name="s" value="<?= $sortBy ?>">
                        <input type="hidden" name="c" value="<?= $consoleIDInput ?>">
                        <input size="28" name="d" type="text" value="<?= $dev ?>">
                        &nbsp;
                        <input type="submit" value="Select">
                    </form>
                </div>
            </div>
        <?php endif ?>
        <div class="largelist">
            <?php
            // Output all console lists fetched
            foreach ($consoleList as $consoleID => $consoleName) {
                if ($consoleIDInput == 0 || $consoleIDInput == $consoleID) {
                    // Cut out empty consoles:
                    $dataExists = false;
                    foreach ($gamesList as $gameEntry) {
                        if ($dev == null) {
                            if ($gameEntry['ConsoleID'] == $consoleID && $gameEntry['NumAchievements'] > 0) {
                                $dataExists = true;
                                break;
                            }
                        } else {
                            if ($gameEntry['ConsoleID'] == $consoleID && $gameEntry['MyAchievements'] > 0) {
                                $dataExists = true;
                                break;
                            }
                        }
                    }
                    if (!$dataExists) {
                        continue;
                    }
                    echo "<h3 class='longheader'>";
                    echo "<a href='gameList.php?c=$consoleID'>";
                    echo "$consoleName";
                    echo "</a>";
                    echo "</h3>";

                    echo "<table><tbody>";

                    $sort1 = ($sortBy == 1) ? 11 : 1;
                    $sort2 = ($sortBy == 2) ? 12 : 2;
                    $sort3 = ($sortBy == 3) ? 13 : 3;
                    $sort4 = ($sortBy == 4) ? 14 : 4;
                    $sort5 = ($sortBy == 5) ? 15 : 5;

                    echo "<tr>";
                    echo "<th></th>";
                    echo "<th><a href='/gameList.php?s=$sort1&d=$dev&c=$consoleIDInput'>Title</a></th>";
                    echo "<th><a href='/gameList.php?s=$sort2&d=$dev&c=$consoleIDInput'>Achievements</a></th>";
                    echo "<th><a href='/gameList.php?s=$sort3&d=$dev&c=$consoleIDInput'>Points</a></th>";
                    echo "<th><a href='/gameList.php?s=$sort4&d=$dev&c=$consoleIDInput'>Leaderboards</a></th>";

                    if ($showTickets) {
                        echo "<th class='text-nowrap'><a href='/gameList.php?s=$sort5&d=$dev&c=$consoleIDInput'>Open Tickets</a></th>";
                    }
                    echo "</tr>";

                    $gameCount = 0;
                    $pointsTally = 0;
                    $achievementsTally = 0;
                    $truePointsTally = 0;
                    $lbCount = 0;
                    if ($showTickets) {
                        $ticketsCount = 0;
                    }

                    foreach ($gamesList as $gameEntry) {
                        if ($gameEntry['ConsoleID'] == $consoleID) {
                            $title = $gameEntry['Title'];
                            $gameID = $gameEntry['ID'];
                            $maxPoints = $gameEntry['MaxPointsAvailable'];
                            $totalTrueRatio = $gameEntry['TotalTruePoints'];
                            if ($dev == null) {
                                $numAchievements = $gameEntry['NumAchievements'];
                            } else {
                                $numAchievements = $gameEntry['MyAchievements'];
                                $totalAchievements = $numAchievements + $gameEntry['NotMyAchievements'];
                            }
                            $numLBs = $gameEntry['NumLBs'];
                            $gameIcon = $gameEntry['GameIcon'];

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
                    }

                    // Totals:
                    echo "<tr>";
                    echo "<td></td>";
                    echo "<td><b>Totals: $gameCount games</b></td>";
                    echo "<td><b>$achievementsTally</b></td>";
                    echo "<td><b>$pointsTally</b><span class='TrueRatio'> ($truePointsTally)</span></td>";
                    echo "<td><b>$lbCount</b></td>";
                    if ($showTickets) {
                        echo "<td><b>$ticketsCount</b></td>";
                    }
                    echo "<td></td>";
                    echo "</tr>";
                    echo "</tbody></table>";
                }
            }
            ?>
            <br>
        </div>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
