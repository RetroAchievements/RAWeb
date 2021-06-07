<?php
require_once __DIR__ . '/../vendor/autoload.php';

RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions);

$dev = requestInputSanitized('u');
$errorCode = requestInputSanitized('e');

$userArchInfo = getUserAchievementInformation($dev);

// Only get stats if the user has a contribute count
if (empty($userArchInfo)) {
    header("Location: " . getenv('APP_URL') . "/user/" . $dev);
    return;
}

$userContribCount = $userArchInfo[0]['ContribCount'];
$userContribYield = $userArchInfo[0]['ContribYield'];

// Get sets and achievements per console data for pie charts
$setsPerConsole = getUserSetsPerConsole($dev);
$achievementsPerConsole = getUserAchievemetnsPerConsole($dev);

// Initialise any dev game variables
$gamesList = [];
$anyDevGameIDs = [];
$anyDevHardestGame = [];
$anyDevEasiestGame = [];
$anyDevRichPresenceCount = 0;
$anyDevLeaderboardCount = 0;
$anyDevLeaderboardTotal = 0;

// Initialise majority dev game variables
$majorityDevGameIDs = [];
$majorityDevHardestGame = [];
$majorityDevEasiestGame = [];
$majorityDevAchievementCount = 0;
$majorityDevRichPresenceCount = 0;
$majorityDevLeaderboardCount = 0;
$majorityDevLeaderboardTotal = 0;

// Initialise sole dev game variables
$onlyDevGameIDs = [];
$onlyDevHardestGame = [];
$onlyDevEasiestGame = [];
$onlyDevAchievementCount = 0;
$onlyDevRichPresenceCount = 0;
$onlyDevLeaderboardCount = 0;
$onlyDevLeaderboardTotal = 0;

// Get user game list data
getGamesListByDev($dev, 0, $gamesList, 1, false);
foreach ($gamesList as $game) {
    $consoleID = $game['ConsoleID'];
    if ($consoleID != 100 && $consoleID != 101) {
        // Any part developer
        if (count($anyDevGameIDs) == 0) {
            $anyDevHardestGame = $game;
            $anyDevEasiestGame = $game;
        } else {
            if (($anyDevHardestGame['TotalTruePoints'] / $anyDevHardestGame['MaxPointsAvailable']) < ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])) {
                $anyDevHardestGame = $game;
            }
            if ($anyDevEasiestGame['TotalTruePoints'] == 0 || ($anyDevEasiestGame['TotalTruePoints'] / $anyDevEasiestGame['MaxPointsAvailable']) < 1 || ($game['TotalTruePoints'] > 0 && (($anyDevEasiestGame['TotalTruePoints'] / $anyDevEasiestGame['MaxPointsAvailable']) > ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])))) {
                $anyDevEasiestGame = $game;
            }
        }
        array_push($anyDevGameIDs, $game['ID']);
        $anyDevRichPresenceCount += $game['RichPresence'];
        $anyDevLeaderboardTotal += $game['NumLBs'];
        if (isset($game['NumLBs'])) {
            $anyDevLeaderboardCount++;
        }

        // Majority developer
        if ($game['MyAchievements'] >= $game['NotMyAchievements']) {
            if (count($majorityDevGameIDs) == 0) {
                $majorityDevHardestGame = $game;
                $majorityDevEasiestGame = $game;
            } else {
                if (($majorityDevHardestGame['TotalTruePoints'] / $majorityDevHardestGame['MaxPointsAvailable']) < ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])) {
                    $majorityDevHardestGame = $game;
                }
                if ($majorityDevEasiestGame['TotalTruePoints'] == 0 || ($majorityDevEasiestGame['TotalTruePoints'] / $majorityDevEasiestGame['MaxPointsAvailable']) < 1 || ($game['TotalTruePoints'] > 0 && (($majorityDevEasiestGame['TotalTruePoints'] / $majorityDevEasiestGame['MaxPointsAvailable']) > ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])))) {
                    $majorityDevEasiestGame = $game;
                }
            }
            array_push($majorityDevGameIDs, $game['ID']);
            $majorityDevAchievementCount += $game['MyAchievements'];
            $majorityDevRichPresenceCount += $game['RichPresence'];
            $majorityDevLeaderboardTotal += $game['NumLBs'];
            if (isset($game['NumLBs'])) {
                $majorityDevLeaderboardCount++;
            }
        }

        // Only developer
        if ($game['MyAchievements'] == $game['NumAchievements']) {
            if (count($onlyDevGameIDs) == 0) {
                $onlyDevHardestGame = $game;
                $onlyDevEasiestGame = $game;
            } else {
                if (($onlyDevHardestGame['TotalTruePoints'] / $onlyDevHardestGame['MaxPointsAvailable']) < ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])) {
                    $onlyDevHardestGame = $game;
                }
                if ($onlyDevEasiestGame['TotalTruePoints'] == 0 || ($onlyDevEasiestGame['TotalTruePoints'] / $onlyDevEasiestGame['MaxPointsAvailable']) < 1 || ($game['TotalTruePoints'] > 0 && (($onlyDevEasiestGame['TotalTruePoints'] / $onlyDevEasiestGame['MaxPointsAvailable']) > ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])))) {
                    $onlyDevEasiestGame = $game;
                }
            }
            array_push($onlyDevGameIDs, $game['ID']);
            $onlyDevAchievementCount += $game['MyAchievements'];
            $onlyDevRichPresenceCount += $game['RichPresence'];
            $onlyDevLeaderboardTotal += $game['NumLBs'];
            if (isset($game['NumLBs'])) {
                $onlyDevLeaderboardCount++;
            }
        }
    }
}

// Initialize any dev game award variables
$anyDevMostCompletedGame = [];
$anyDevMostMasteredGame = [];

// Get user award data for any developed games
$anyDevCompletedMasteredGames = getMostAwardedGames($anyDevGameIDs);
foreach ($anyDevCompletedMasteredGames as $game) {
    if (count($anyDevMostCompletedGame) == 0) {
        if ($game['Completed'] > 0) {
            $anyDevMostCompletedGame = $game;
        }
    } else {
        if ($anyDevMostCompletedGame['Completed'] < $game['Completed']) {
            $anyDevMostCompletedGame = $game;
        }
    }

    if (count($anyDevMostMasteredGame) == 0) {
        if ($game['Mastered'] > 0) {
            $anyDevMostMasteredGame = $game;
        }
    } else {
        if ($anyDevMostMasteredGame['Mastered'] < $game['Mastered']) {
            $anyDevMostMasteredGame = $game;
        }
    }
}

// Initialize any dev user award variables
$anyDevOwnAwards = [];
$anyDevCompletedAwards = 0;
$anyDevMasteredAwards = 0;
$anyDevUserMostCompleted = [];
$anyDevUserMostMastered = [];

// Get user award data for any developed games
$anyDevAwardInfo = getMostAwardedUsers($anyDevGameIDs);
foreach ($anyDevAwardInfo as $userInfo) {
    if (count($anyDevUserMostCompleted) == 0) {
        if ($userInfo['Completed'] > 0) {
            $anyDevUserMostCompleted = $userInfo;
        }
    } else {
        if ($anyDevUserMostCompleted['Completed'] < $userInfo['Completed']) {
            $anyDevUserMostCompleted = $userInfo;
        }
    }

    if (count($anyDevUserMostMastered) == 0) {
        if ($userInfo['Mastered'] > 0) {
            $anyDevUserMostMastered = $userInfo;
        }
    } else {
        if ($anyDevUserMostMastered['Mastered'] < $userInfo['Mastered']) {
            $anyDevUserMostMastered = $userInfo;
        }
    }

    if (strcmp($dev, $userInfo['User']) == 0) {
        $anyDevOwnAwards = $userInfo;
    }
    $anyDevCompletedAwards += $userInfo['Completed'];
    $anyDevMasteredAwards += $userInfo['Mastered'];
}

// Initialize majority dev game award variables
$majorityDevMostCompletedGame = [];
$majorityDevMostMasteredGame = [];

// Get user award data for majority developed games
$majorityDevCompletedMasteredGames = getMostAwardedGames($majorityDevGameIDs);
foreach ($majorityDevCompletedMasteredGames as $game) {
    if (count($majorityDevMostCompletedGame) == 0) {
        if ($game['Completed'] > 0) {
            $majorityDevMostCompletedGame = $game;
        }
    } else {
        if ($majorityDevMostCompletedGame['Completed'] < $game['Completed']) {
            $majorityDevMostCompletedGame = $game;
        }
    }

    if (count($majorityDevMostMasteredGame) == 0) {
        if ($game['Mastered'] > 0) {
            $majorityDevMostMasteredGame = $game;
        }
    } else {
        if ($majorityDevMostMasteredGame['Mastered'] < $game['Mastered']) {
            $majorityDevMostMasteredGame = $game;
        }
    }
}

// Initialize majority dev user award variables
$majorityDevOwnAwards = [];
$majorityDevCompletedAwards = 0;
$majorityDevMasteredAwards = 0;
$majorityDevUserMostCompleted = [];
$majorityDevUserMostMastered = [];

// Get user award data for majority developed games
$majorityDevAwardInfo = getMostAwardedUsers($majorityDevGameIDs);
foreach ($majorityDevAwardInfo as $userInfo) {
    if (count($majorityDevUserMostCompleted) == 0) {
        if ($userInfo['Completed'] > 0) {
            $majorityDevUserMostCompleted = $userInfo;
        }
    } else {
        if ($majorityDevUserMostCompleted['Completed'] < $userInfo['Completed']) {
            $majorityDevUserMostCompleted = $userInfo;
        }
    }

    if (count($majorityDevUserMostMastered) == 0) {
        if ($userInfo['Mastered'] > 0) {
            $majorityDevUserMostMastered = $userInfo;
        }
    } else {
        if ($majorityDevUserMostMastered['Mastered'] < $userInfo['Mastered']) {
            $majorityDevUserMostMastered = $userInfo;
        }
    }

    if (strcmp($dev, $userInfo['User']) == 0) {
        $majorityDevOwnAwards = $userInfo;
    }
    $majorityDevCompletedAwards += $userInfo['Completed'];
    $majorityDevMasteredAwards += $userInfo['Mastered'];
}

// Initialize sole dev game award variables
$onlyDevMostCompletedGame = [];
$onlyDevMostMasteredGame = [];

// Get user award data for solely developed games
$onlyDevCompletedMasteredGames = getMostAwardedGames($onlyDevGameIDs);
foreach ($onlyDevCompletedMasteredGames as $game) {
    if (count($onlyDevMostCompletedGame) == 0) {
        if ($game['Completed'] > 0) {
            $onlyDevMostCompletedGame = $game;
        }
    } else {
        if ($onlyDevMostCompletedGame['Completed'] < $game['Completed']) {
            $onlyDevMostCompletedGame = $game;
        }
    }

    if (count($onlyDevMostMasteredGame) == 0) {
        if ($game['Mastered'] > 0) {
            $onlyDevMostMasteredGame = $game;
        }
    } else {
        if ($onlyDevMostMasteredGame['Mastered'] < $game['Mastered']) {
            $onlyDevMostMasteredGame = $game;
        }
    }
}

// Initialize sole dev user award variables
$onlyDevOwnAwards = [];
$onlyDevCompletedAwards = 0;
$onlyDevMasteredAwards = 0;
$onlyDevUserMostCompleted = [];
$onlyDevUserMostMastered = [];

// Get user award data for solely developed games
$onlyDevAwardInfo = getMostAwardedUsers($onlyDevGameIDs);
foreach ($onlyDevAwardInfo as $userInfo) {
    if (count($onlyDevUserMostCompleted) == 0) {
        if ($userInfo['Completed'] > 0) {
            $onlyDevUserMostCompleted = $userInfo;
        }
    } else {
        if ($onlyDevUserMostCompleted['Completed'] < $userInfo['Completed']) {
            $onlyDevUserMostCompleted = $userInfo;
        }
    }

    if (count($onlyDevUserMostMastered) == 0) {
        if ($userInfo['Mastered'] > 0) {
            $onlyDevUserMostMastered = $userInfo;
        }
    } else {
        if ($onlyDevUserMostMastered['Mastered'] < $userInfo['Mastered']) {
            $onlyDevUserMostMastered = $userInfo;
        }
    }

    if (strcmp($dev, $userInfo['User']) == 0) {
        $onlyDevOwnAwards = $userInfo;
    }
    $onlyDevCompletedAwards += $userInfo['Completed'];
    $onlyDevMasteredAwards += $userInfo['Mastered'];
}

// Initialize user achievement variables
$defaultBadges = [
    "00000",
    "00080",
    "00083",
    "00084",
    "00085",
    "00132",
    "00133",
    "00134",
    "00135",
    "00136",
    "00137",
];
$achievementCount = 0;
$totalMemLegth = 0;
$customBadgesCount = 0;
$totalPoints = 0;
$totalTruePoints = 0;
$shortestMemAchievement = [];
$longestMemAchievement = [];
$easiestAchievement = [];
$hardestAchievement = [];
$firstAchievement = [];
$lastAchievement = [];
$achievementIDs = [];
foreach ($userArchInfo as $achievement) {
    if ($achievementCount == 0) {
        $shortestMemAchievement = $achievement;
        $longestMemAchievement = $achievement;
        $easiestAchievement = $achievement;
        $hardestAchievement = $achievement;
        $firstAchievement = $achievement;
        $lastAchievement = $achievement;
    } else {
        if ($hardestAchievement['Points'] && $achievement['Points'] && ($hardestAchievement['TrueRatio'] / $hardestAchievement['Points']) < ($achievement['TrueRatio'] / $achievement['Points'])) {
            $hardestAchievement = $achievement;
        }
        if ($easiestAchievement['TrueRatio'] == 0 || ($achievement['TrueRatio'] > 0 && (($easiestAchievement['TrueRatio'] / $easiestAchievement['Points']) > ($achievement['TrueRatio'] / $achievement['Points'])))) {
            $easiestAchievement = $achievement;
        }
        if ($shortestMemAchievement['MemLength'] > $achievement['MemLength']) {
            $shortestMemAchievement = $achievement;
        }
        if ($longestMemAchievement['MemLength'] < $achievement['MemLength']) {
            $longestMemAchievement = $achievement;
        }
    }

    if (!in_array($achievement['BadgeName'], $defaultBadges)) {
        $customBadgesCount++;
    }
    $achievementCount++;
    $totalMemLegth += $achievement['MemLength'];
    $totalPoints += $achievement['Points'];
    $totalTruePoints += $achievement['TrueRatio'];
    $lastAchievement = $achievement;
    array_push($achievementIDs, $achievement['ID']);
}

$averagePoints = $totalPoints / $achievementCount;
$averageTruePoints = $totalTruePoints / $achievementCount;
$averageMemLength = $totalMemLegth / $achievementCount;

// Get own achievements earned info
$ownAchievementsObtained = getOwnAchievementsObtained($dev);

// Initialize unique obtainers variables
$uniqueObtainers = 0;
$mostAchievementObtainer = [];

// Get unique obtainers for user
$obtainers = getObtainersOfSpecificUser($dev);
foreach ($obtainers as $obtainer) {
    if ($uniqueObtainers == 0) {
        $mostAchievementObtainer = $obtainer;
    } else {
        if ($mostAchievementObtainer['ObtainCount'] < $obtainer['ObtainCount']) {
            $mostAchievementObtainer = $obtainer;
        }
    }
    $uniqueObtainers++;
}

// Get last 200 achievements obtained by others
// Only 100 will be displayed but 200 are needed to remove potential SC HC duplicates
$maxRecentAchievements = 200;
$recentlyObtainedAchievements = getRecentObtainedAchievements($achievementIDs, 0, $maxRecentAchievements);

// Initialize code note variables
$mostNotedGame = [];
$userCodeNoteCount = 0;

// Get code note information for user
$codeNotes = getCodeNoteCounts($dev);
foreach ($codeNotes as $game) {
    if (count($mostNotedGame) == 0) {
        $mostNotedGame = $game;
    }
    $userCodeNoteCount += $game['NoteCount'];
}

// Initialize ticket information variables
$userTickets['total'] = 0;
$userTickets['closed'] = 0;
$userTickets['open'] = 0;
$userTickets['resolved'] = 0;
$userTickets['uniqueTotal'] = 0;
$userTickets['uniqueClosed'] = 0;
$userTickets['uniqueOpen'] = 0;
$userTickets['uniqueResolved'] = 0;
$prevID = 0;

// Get ticket information for user
$userTicketInfo = getTicketsForUser($dev);
foreach ($userTicketInfo as $ticketData) {
    switch ($ticketData['ReportState']) {
        case 0:
            $userTickets['closed'] += $ticketData['TicketCount'];
            $userTickets['total'] += $ticketData['TicketCount'];
            $userTickets['uniqueClosed']++;
            break;
        case 1:
            $userTickets['open'] += $ticketData['TicketCount'];
            $userTickets['total'] += $ticketData['TicketCount'];
            $userTickets['uniqueOpen']++;
            break;
        case 2:
            $userTickets['resolved'] += $ticketData['TicketCount'];
            $userTickets['total'] += $ticketData['TicketCount'];
            $userTickets['uniqueResolved']++;
            break;
    }
    if ($prevID != $ticketData['AchievementID']) {
        $prevID = $ticketData['AchievementID'];
        $userTickets['uniqueTotal']++;
    }
}

// Get most ticketed game and achievement
$mostTicketedGame = getUserGameWithMostTickets($dev);
$mostTicketedAchievement = getUserAchievementWithMostTickets($dev);

// Get user who created most tickets
$mostTicketCreator = getUserWhoCreatedMostTickets($dev);

// Get closed/resolved ticket information for user
$userCount = 0;
$closedTicketPlusMinus = 0;
$closedTicketPlusMinusRatio = 0;
$resolvedTicketPlusMinus = 0;
$resolvedTicketPlusMinusRatio = 0;
$totalTicketPlusMinus = 0;
$totalTicketPlusMinusRatio = 0;
$closedResolvedTicketInfo = [];
$closedResolvedTicketInfo['Count'] = 0;
$closedResolvedTicketInfo['ClosedCount'] = 0;
$closedResolvedTicketInfo['ResolvedCount'] = 0;

// Get closed/resolved ticket information
$ticketsClosedResolved = getNumberOfTicketsClosedForOthers($dev);
foreach ($ticketsClosedResolved as $ticketData) {
    if ($userCount == 0) {
        $closedResolvedTicketInfo['ClosedAuthor'] = $ticketData['Author'];
        $closedResolvedTicketInfo['ClosedAuthorCount'] = $ticketData['ClosedCount'];
        $closedResolvedTicketInfo['ResolvedAuthor'] = $ticketData['Author'];
        $closedResolvedTicketInfo['ResolvedAuthorCount'] = $ticketData['ResolvedCount'];
    } else {
        if ($closedResolvedTicketInfo['ClosedAuthorCount'] < $ticketData['ClosedCount']) {
            $closedResolvedTicketInfo['ClosedAuthor'] = $ticketData['Author'];
            $closedResolvedTicketInfo['ClosedAuthorCount'] = $ticketData['ClosedCount'];
        }
        if ($closedResolvedTicketInfo['ResolvedAuthorCount'] < $ticketData['ResolvedCount']) {
            $closedResolvedTicketInfo['ResolvedAuthor'] = $ticketData['Author'];
            $closedResolvedTicketInfo['ResolvedAuthorCount'] = $ticketData['ResolvedCount'];
        }
    }
    $userCount++;
    $closedResolvedTicketInfo['Count'] += $ticketData['TicketCount'];
    $closedResolvedTicketInfo['ClosedCount'] += $ticketData['ClosedCount'];
    $closedResolvedTicketInfo['ResolvedCount'] += $ticketData['ResolvedCount'];
}
$closedTicketPlusMinus = $closedResolvedTicketInfo['ClosedCount'] - $userTickets['closed'];
$closedTicketPlusMinus = ($closedTicketPlusMinus > 0) ? '+' . $closedTicketPlusMinus : $closedTicketPlusMinus;
$resolvedTicketPlusMinus = $closedResolvedTicketInfo['ResolvedCount'] - $userTickets['resolved'];
$resolvedTicketPlusMinus = ($resolvedTicketPlusMinus > 0) ? '+' . $resolvedTicketPlusMinus : $resolvedTicketPlusMinus;
$totalTicketPlusMinus = $closedResolvedTicketInfo['Count'] - $userTickets['total'];
$totalTicketPlusMinus = ($totalTicketPlusMinus > 0) ? '+' . $totalTicketPlusMinus : $totalTicketPlusMinus;
if ($userTickets['closed'] == 0) {
    $closedTicketPlusMinusRatio = $closedResolvedTicketInfo['ClosedCount'];
} else {
    $closedTicketPlusMinusRatio = $closedResolvedTicketInfo['ClosedCount'] / $userTickets['closed'];
}
if ($userTickets['resolved'] == 0) {
    $resolvedTicketPlusMinusRatio = $closedResolvedTicketInfo['ResolvedCount'];
} else {
    $resolvedTicketPlusMinusRatio = $closedResolvedTicketInfo['ResolvedCount'] / $userTickets['resolved'];
}
if ($userTickets['total'] == 0) {
    $totalTicketPlusMinusRatio = $closedResolvedTicketInfo['Count'];
} else {
    $totalTicketPlusMinusRatio = $closedResolvedTicketInfo['Count'] / $userTickets['total'];
}

RenderHtmlStart();
RenderHtmlHead("$dev's Developer Stats");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

    function drawChart() {
        <?php if ($userContribCount > 0) { ?>
        var gamesData = google.visualization.arrayToDataTable([
        ['System', 'Number of Sets'],
        <?php
            $count = 0;
            foreach ($setsPerConsole as $info) {
                if ($count++ > 0) {
                    echo ", ";
                }
                echo json_encode([
                    $info['ConsoleName'],
                    (int) $info['SetCount'],
                ]);
            }
            ?>
        ]);

        var achievementData = google.visualization.arrayToDataTable([
        ['Console', 'Achievement per console'],
        <?php
            $count = 0;
            foreach ($achievementsPerConsole as $info) {
                if ($count++ > 0) {
                    echo ", ";
                }
                echo json_encode([
                    $info['ConsoleName'],
                    (int) $info['AchievementCount'],
                ]);
            }
            ?>
        ]);

        let chartWidth = 450;
        let chartAreaHeight = '60%';

        /* Render smaller charts on mobile */
        if(window.innerWidth < 640){
            chartWidth = 300;
            chartAreaHeight = '50%';
        }

        var gameOptions = {
            title: 'Games Developed for Per Console',
               'width': chartWidth,
               'height': 325,
            'chartArea': {'width': '100%', 'height': chartAreaHeight},
            pieSliceText: 'value-and-percentage',
            titleTextStyle: {
                color: '#2C97FA',
                bold: true,
                fontSize: 20
            },
            legend: {
                position: 'right',
                alignment: 'center',
                textStyle: {
                    color: '#2C97FA',
                    fontSize: 12
                }
            },
            backgroundColor: 'transparent',
            colors: ['#000066', '#000099', '#0000cc', '#0000ff', '#3333ff', '#6666ff', '#9999ff'] //blue
            //colors: ['#660000', '#990000', '#cc0000', '#ff0000', '#ff3333', '#ff6666', '#ff9999'] //red
            //colors: ['#003300', '#004d00', '#006600', '#008000', '#009900', '#00b300', '#00cc00'] //green
            //colors: ['#660029', '#99003d', '#cc0052', '#ff0066', '#ff3385', '#ff66a3', '#ff99c2'] //pink
            //colors: ['#333333', '#4d4d4d', '#666666', '#808080', '#999999', '#b3b3b3', '#cccccc'] //B/W
        };

        var achievementOptions = {
            title: 'Achievements Created Per Console',
               'width': chartWidth,
               'height': 325,
            'chartArea': {'width': '100%', 'height': chartAreaHeight},
            pieSliceText: 'value-and-percentage',
            titleTextStyle: {
                color: '#2C97FA',
                bold: true,
                fontSize: 20
            },
            legend: {
                position: 'right',
                alignment: 'center',
                maxLines: 5,
                textStyle: {
                    color: '#2C97FA',
                    fontSize: 12
                }
            },
            backgroundColor: 'transparent',
            colors: ['#000066', '#000099', '#0000cc', '#0000ff', '#3333ff', '#6666ff', '#9999ff']
        };

        var gameChart = new google.visualization.PieChart(document.getElementById('gameChart'));
        var achievementChart = new google.visualization.PieChart(document.getElementById('achievementChart'));

        gameChart.draw(gamesData, gameOptions);
        achievementChart.draw(achievementData, achievementOptions);
        <?php } ?>
    }
</script>
<div id="mainpage">
    <div id='fullcontainer'>
        <div class="navpath">
            <?php
                echo "<b><a href='/userList.php'>All Users</a> &raquo; <a href='/user/$dev'>$dev</a> &raquo; Developer Stats</b>";
            ?>
        </div>

        <?php if ($user !== null): ?>
            <div class="d-flex flex-wrap justify-content-between">
                <div>
                </div>
                <div>
                    Filter by developer:<br>
                    <form action="individualdevstats.php">
                        <input size="28" name="u" type="text" value="<?= $dev ?>">
                        &nbsp;
                        <input type="submit" value="Select">
                    </form>
                </div>
            </div>
        <?php endif ?>

        <?php
        echo "<h2>$dev's Developer Stats</h2>";

        // Only show stats if the user has a contribute count
        if ($userContribCount > 0) {
            /*
             * Pie Charts
             */
            echo "<div style='width: 100%; overflow: hidden; text-align: center'>";
            echo "<div style='display: inline-block' id='gameChart'></div>";
            echo "<div style='display: inline-block' id='achievementChart'></div>";
            echo "</div>";

            /*
             * Games
             */
            echo "<H1>Games</H1>";

            /*
             * Any Development
             */
            echo "<table><tbody>";
            echo "<tr><td colspan='2' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Any Development</td></tr>";
            echo "<tr></tr><tr><td colspan='2' align='center'>Stats below are for games that $dev has published at least one achievement for.</td></tr>";

            // Any Development - Games developed for
            echo "<tr><td width='50%'>Games Developed For:</td><td>" . count($anyDevGameIDs) . "</td></tr>";

            // Any Development - Games with Rich Presence
            echo "<tr><td>Games with Rich Presence:</td><td>";
            if (count($anyDevGameIDs) > 0) {
                echo $anyDevRichPresenceCount . " - " . number_format($anyDevRichPresenceCount / count($anyDevGameIDs) * 100, 2, '.', '') . "%";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - Games with Leaderboards and Leaderboard count
            echo "<tr><td>Games with Leaderboards:</td><td>";
            if (count($anyDevGameIDs) > 0) {
                echo $anyDevLeaderboardCount . " - " . number_format($anyDevLeaderboardCount / count($anyDevGameIDs) * 100, 2, '.', '') . "%</br>" . $anyDevLeaderboardTotal . " Unique Leaderboards";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - Easiest game by retro ratio
            echo "<tr><td>Easiest Game by Retro Ratio:</td><td>";
            if (count($anyDevEasiestGame) > 0) {
                echo number_format($anyDevEasiestGame['TotalTruePoints'] / $anyDevEasiestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
                echo GetGameAndTooltipDiv($anyDevEasiestGame['ID'], $anyDevEasiestGame['Title'], $anyDevEasiestGame['GameIcon'], $anyDevEasiestGame['ConsoleName'], false, 32);
                echo "</br>" . $anyDevEasiestGame['MyAchievements'] . " of " . $anyDevEasiestGame['NumAchievements'] . " Achievements Created";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - Hardest game by retro ratio
            echo "<tr><td>Hardest Game by Retro Ratio</td><td>";
            if (count($anyDevHardestGame) > 0) {
                echo number_format($anyDevHardestGame['TotalTruePoints'] / $anyDevHardestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
                echo GetGameAndTooltipDiv($anyDevHardestGame['ID'], $anyDevHardestGame['Title'], $anyDevHardestGame['GameIcon'], $anyDevHardestGame['ConsoleName'], false, 32);
                echo "</br>" . $anyDevHardestGame['MyAchievements'] . " of " . $anyDevHardestGame['NumAchievements'] . " Achievements Created</br>";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - Complete/Mastered games
            echo "<tr><td>Completed/Mastered Awards:</td><td>";
            if (count($anyDevGameIDs) > 0) {
                echo $anyDevCompletedAwards . " <b>(" . $anyDevMasteredAwards . ")</br>";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - Own Complete/Mastered games
            echo "<tr><td>Own Completed/Mastered Awards:</td><td>";
            if (count($anyDevOwnAwards) > 0) {
                echo $anyDevOwnAwards['Completed'] . " <b>(" . $anyDevOwnAwards['Mastered'] . ")</br>";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - Most completed game
            echo "<tr><td>Most Completed Game:</td><td>";
            if (count($anyDevMostCompletedGame) > 0) {
                echo $anyDevMostCompletedGame['Completed'] . " - ";
                echo GetGameAndTooltipDiv($anyDevMostCompletedGame['ID'], $anyDevMostCompletedGame['Title'], $anyDevMostCompletedGame['GameIcon'], $anyDevMostCompletedGame['ConsoleName'], false, 32);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - Most mastered game
            echo "<tr><td>Most Mastered Game:</td><td>";
            if (count($anyDevMostMasteredGame) > 0) {
                echo $anyDevMostMasteredGame['Mastered'] . " - ";
                echo GetGameAndTooltipDiv($anyDevMostMasteredGame['ID'], $anyDevMostMasteredGame['Title'], $anyDevMostMasteredGame['GameIcon'], $anyDevMostMasteredGame['ConsoleName'], false, 32);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - User with most completed awards
            echo "<tr><td>User with Most Completed Awards:</td><td>";
            if (count($anyDevUserMostCompleted) > 0) {
                echo $anyDevUserMostCompleted['Completed'] . " - ";
                echo GetUserAndTooltipDiv($anyDevUserMostCompleted['User'], true);
                echo GetUserAndTooltipDiv($anyDevUserMostCompleted['User'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Any Development - User with most mastered awards
            echo "<tr><td>User with Most Mastered Awards:</td><td>";
            if (count($anyDevUserMostMastered) > 0) {
                echo $anyDevUserMostMastered['Mastered'] . " - ";
                echo GetUserAndTooltipDiv($anyDevUserMostMastered['User'], true);
                echo GetUserAndTooltipDiv($anyDevUserMostMastered['User'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";
            echo "</table></tbody>";
            echo "</br>";

            /*
             * Majority Developer
             */
            echo "<table><tbody>";
            echo "<tr><td colspan='2' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Majority Developer</td></tr>";
            echo "<tr></tr><tr><td colspan='2' align='center'>Stats below are for games that $dev has published at least half the achievements for.</td></tr>";

            // Majority Developer - Games developed for
            echo "<tr><td width='50%'>Games Developed For:</td><td>" . count($majorityDevGameIDs) . "</td></tr>";

            // Majority Developer - Games with Rich Presence
            echo "<tr><td>Games with Rich Presence:</td><td>";
            if (count($majorityDevGameIDs) > 0) {
                echo $majorityDevRichPresenceCount . " - " . number_format($majorityDevRichPresenceCount / count($majorityDevGameIDs) * 100, 2, '.', '') . "%";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - Games with Leaderboards and Leaderboard count
            echo "<tr><td>Games with Leaderboards:</td><td>";
            if (count($majorityDevGameIDs) > 0) {
                echo $majorityDevLeaderboardCount . " - " . number_format($majorityDevLeaderboardCount / count($majorityDevGameIDs) * 100, 2, '.', '') . "%</br>" . $majorityDevLeaderboardTotal . " Unique Leaderboards";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - Easiest game by retro ratio
            echo "<tr><td>Easiest Game by Retro Ratio:</td><td>";
            if (count($majorityDevEasiestGame) > 0) {
                echo number_format($majorityDevEasiestGame['TotalTruePoints'] / $majorityDevEasiestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
                echo GetGameAndTooltipDiv($majorityDevEasiestGame['ID'], $majorityDevEasiestGame['Title'], $majorityDevEasiestGame['GameIcon'], $majorityDevEasiestGame['ConsoleName'], false, 32);
                echo "</br>" . $majorityDevEasiestGame['MyAchievements'] . " of " . $majorityDevEasiestGame['NumAchievements'] . " Achievements Created";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - Hardest game by retro ratio
            echo "<tr><td>Hardest Game by Retro Ratio:</td><td>";
            if (count($majorityDevHardestGame) > 0) {
                echo number_format($majorityDevHardestGame['TotalTruePoints'] / $majorityDevHardestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
                echo GetGameAndTooltipDiv($majorityDevHardestGame['ID'], $majorityDevHardestGame['Title'], $majorityDevHardestGame['GameIcon'], $majorityDevHardestGame['ConsoleName'], false, 32);
                echo "</br>" . $majorityDevHardestGame['MyAchievements'] . " of " . $majorityDevHardestGame['NumAchievements'] . " Achievements Created";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - Complete/Mastered games
            echo "<tr><td>Completed/Mastered Awards:</td><td>";
            if (count($majorityDevGameIDs) > 0) {
                echo $majorityDevCompletedAwards . " <b>(" . $majorityDevMasteredAwards . ")</br>";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - Own Complete/Mastered games
            echo "<tr><td>Own Completed/Mastered Awards:</td><td>";
            if (count($majorityDevOwnAwards) > 0) {
                echo $majorityDevOwnAwards['Completed'] . " <b>(" . $majorityDevOwnAwards['Mastered'] . ")</br>";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - Most completed game
            echo "<tr><td>Most Completed Game:</td><td>";
            if (count($majorityDevMostCompletedGame) > 0) {
                echo $majorityDevMostCompletedGame['Completed'] . " - ";
                echo GetGameAndTooltipDiv($majorityDevMostCompletedGame['ID'], $majorityDevMostCompletedGame['Title'], $majorityDevMostCompletedGame['GameIcon'], $majorityDevMostCompletedGame['ConsoleName'], false, 32);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - Most mastered game
            echo "<tr><td>Most Mastered Game:</td><td>";
            if (count($majorityDevMostMasteredGame) > 0) {
                echo $majorityDevMostMasteredGame['Mastered'] . " - ";
                echo GetGameAndTooltipDiv($majorityDevMostMasteredGame['ID'], $majorityDevMostMasteredGame['Title'], $majorityDevMostMasteredGame['GameIcon'], $majorityDevMostMasteredGame['ConsoleName'], false, 32);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - User with most completed awards
            echo "<tr><td>User with Most Completed Awards:</td><td>";
            if (count($majorityDevUserMostCompleted) > 0) {
                echo $majorityDevUserMostCompleted['Completed'] . " - ";
                echo GetUserAndTooltipDiv($majorityDevUserMostCompleted['User'], true);
                echo GetUserAndTooltipDiv($majorityDevUserMostCompleted['User'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Majority Developer - User with most mastered awards
            echo "<tr><td>User with Most Mastered Awards:</td><td>";
            if (count($majorityDevUserMostMastered) > 0) {
                echo $majorityDevUserMostMastered['Mastered'] . " - ";
                echo GetUserAndTooltipDiv($majorityDevUserMostMastered['User'], true);
                echo GetUserAndTooltipDiv($majorityDevUserMostMastered['User'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";
            echo "</table></tbody>";
            echo "</br>";

            /*
             * Sole Developer
             */
            echo "<table><tbody>";
            echo "<tr><td colspan='2' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Sole Developer</td></tr>";
            echo "<tr></tr><tr><td colspan='2' align='center'>Stats below are for games that $dev has published all the achievements for.</td></tr>";

            // Sole Developer - Games developed for
            echo "<tr><td width='50%'>Games Developed For:</td><td>" . count($onlyDevGameIDs) . "</td></tr>";

            // Sole Developer - Games with Rich Presence
            echo "<tr><td>Games with Rich Presence:</td><td>";
            if (count($onlyDevGameIDs) > 0) {
                echo $onlyDevRichPresenceCount . " - " . number_format($onlyDevRichPresenceCount / count($onlyDevGameIDs) * 100, 2, '.', '') . "%";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Games with Leaderboards and Leaderboard count
            echo "<tr><td>Games with Leaderboards:</td><td>";
            if (count($onlyDevGameIDs) > 0) {
                echo $onlyDevLeaderboardCount . " - " . number_format($onlyDevLeaderboardCount / count($onlyDevGameIDs) * 100, 2, '.', '') . "%</br>" . $onlyDevLeaderboardTotal . " Unique Leaderboards";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Easiest game by retro ratio
            echo "<tr><td>Easiest Game by Retro Ratio:</td><td>";
            if (count($onlyDevEasiestGame) > 0) {
                echo number_format($onlyDevEasiestGame['TotalTruePoints'] / $onlyDevEasiestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
                echo GetGameAndTooltipDiv($onlyDevEasiestGame['ID'], $onlyDevEasiestGame['Title'], $onlyDevEasiestGame['GameIcon'], $onlyDevEasiestGame['ConsoleName'], false, 32);
                echo "</br>" . $onlyDevEasiestGame['MyAchievements'] . " of " . $onlyDevEasiestGame['NumAchievements'] . " Achievements Created";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Hardest game by retro ratio
            echo "<tr><td>Hardest Game by Retro Ratio:</td><td>";
            if (count($onlyDevHardestGame) > 0) {
                echo number_format($onlyDevHardestGame['TotalTruePoints'] / $onlyDevHardestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
                echo GetGameAndTooltipDiv($onlyDevHardestGame['ID'], $onlyDevHardestGame['Title'], $onlyDevHardestGame['GameIcon'], $onlyDevHardestGame['ConsoleName'], false, 32);
                echo "</br>" . $onlyDevHardestGame['MyAchievements'] . " of " . $onlyDevHardestGame['NumAchievements'] . " Achievements Created";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Complete/Mastered games
            echo "<tr><td>Completed/Mastered Awards:</td><td>";
            if (count($onlyDevGameIDs) > 0) {
                echo $onlyDevCompletedAwards . " <b>(" . $onlyDevMasteredAwards . ")</br>";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Own Complete/Mastered games
            echo "<tr><td>Own Completed/Mastered Awards:</td><td>";
            if (count($onlyDevOwnAwards) > 0) {
                echo $onlyDevOwnAwards['Completed'] . " <b>(" . $onlyDevOwnAwards['Mastered'] . ")</br>";
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Most completed game
            echo "<tr><td>Most Completed Game:</td><td>";
            if (count($onlyDevMostCompletedGame) > 0) {
                echo $onlyDevMostCompletedGame['Completed'] . " - ";
                echo GetGameAndTooltipDiv($onlyDevMostCompletedGame['ID'], $onlyDevMostCompletedGame['Title'], $onlyDevMostCompletedGame['GameIcon'], $onlyDevMostCompletedGame['ConsoleName'], false, 32);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Most mastered game
            echo "<tr><td>Most Mastered Game:</td><td>";
            if (count($onlyDevMostMasteredGame) > 0) {
                echo $onlyDevMostMasteredGame['Mastered'] . " - ";
                echo GetGameAndTooltipDiv($onlyDevMostMasteredGame['ID'], $onlyDevMostMasteredGame['Title'], $onlyDevMostMasteredGame['GameIcon'], $onlyDevMostMasteredGame['ConsoleName'], false, 32);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - User with most completed awards
            echo "<tr><td>User with Most Completed Awards:</td><td>";
            if (count($onlyDevUserMostCompleted) > 0) {
                echo $onlyDevUserMostCompleted['Completed'] . " - ";
                echo GetUserAndTooltipDiv($onlyDevUserMostCompleted['User'], true);
                echo GetUserAndTooltipDiv($onlyDevUserMostCompleted['User'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - User with most mastered awards
            echo "<tr><td>User with Most Mastered Awards:</td><td>";
            if (count($onlyDevUserMostMastered) > 0) {
                echo $onlyDevUserMostMastered['Mastered'] . " - ";
                echo GetUserAndTooltipDiv($onlyDevUserMostMastered['User'], true);
                echo GetUserAndTooltipDiv($onlyDevUserMostMastered['User'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";
            echo "</table></tbody>";
            echo "</br></br>";

            /*
             * Achievements
             */
            echo "<H1>Achievements</H1>";
            echo "<table><tbody>";

            // Any Development - Achievements created
            echo "<tr><td width='50%'>Achievements Created:</td><td>" . $achievementCount . "</td></tr>";

            // Majority Developer - Achievements created
            echo "<tr><td>Achievements Created (Majority Developer):</td><td>";
            if (count($majorityDevGameIDs) > 0) {
                echo $majorityDevAchievementCount;
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Achievements created
            echo "<tr><td>Achievements Created (Sole Developer):</td><td>";
            if (count($onlyDevGameIDs) > 0) {
                echo $onlyDevAchievementCount;
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Achievementes with custom badges
            echo "<tr><td>Achievements with Custom Badges:</td><td>" . $customBadgesCount . " (" . number_format($customBadgesCount / $achievementCount * 100, 2, '.', '') . "%)</td></tr>";

            // Average achievement points
            echo "<tr><td>Average Achievement Points:</td><td>" . number_format($averagePoints, 2, '.', '') . "</td></tr>";

            // Average achievement retro points and retro ratio
            echo "<tr><td>Average Achievement Retro Points (Average Retro Ratio):</td><td>" . number_format($averageTruePoints, 2, '.', '') . " (" . number_format($averageTruePoints / $averagePoints, 2, '.', '') . ")</td></tr>";

            // Average achievement memory length
            echo "<tr><td>Average Achievement Memory Length:</td><td>" . number_format($averageMemLength, 2, '.', '') . "</td></tr>";

            // Shortest achievement by memory length
            echo "<tr><td>Shortest Achievement by Memory Length:</td><td>" . $shortestMemAchievement['MemLength'] . " - ";
            echo GetAchievementAndTooltipDiv($shortestMemAchievement['ID'], $shortestMemAchievement['Title'], $shortestMemAchievement['Description'], $shortestMemAchievement['Points'], $shortestMemAchievement['GameTitle'], $shortestMemAchievement['BadgeName'], true, false, '', 32);
            echo "</td></tr>";

            // Longest achievement by memory length
            echo "<tr><td>Longest Achievement by Memory Length:</td><td>" . $longestMemAchievement['MemLength'] . " - ";
            echo GetAchievementAndTooltipDiv($longestMemAchievement['ID'], $longestMemAchievement['Title'], $longestMemAchievement['Description'], $longestMemAchievement['Points'], $longestMemAchievement['GameTitle'], $longestMemAchievement['BadgeName'], true, false, '', 32);
            echo "</td></tr>";

            // Any Development - Average achievement count per game
            echo "<tr><td>Average Achievement Count per Game:</td><td>" . number_format($achievementCount / count($anyDevGameIDs), 2, '.', '') . "</td></tr>";

            // Majority Developer - Average achievement count per game
            echo "<tr><td>Average Achievement Count per Game (Majority Developer):</td><td>";
            if (count($majorityDevGameIDs) > 0) {
                echo number_format($majorityDevAchievementCount / count($majorityDevGameIDs), 2, '.', '');
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Sole Developer - Average achievement count per game
            echo "<tr><td>Average Achievement Count per Game (Sole Developer):</td><td>";
            if (count($onlyDevGameIDs) > 0) {
                echo number_format($onlyDevAchievementCount / count($onlyDevGameIDs), 2, '.', '');
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Achievements won by others
            echo "<tr><td>Achievements Won by Others:</td><td>$userContribCount</td></tr>";

            // Achievement poits awarded to others
            echo "<tr><td>Points Awarded to Others:</td><td>$userContribYield</td></tr>";

            // Number of own achievements obtained
            echo "<tr><td>Own Achievements Obtained:</td><td>";
            if ($ownAchievementsObtained['SoftcoreCount'] > 0) {
                echo $ownAchievementsObtained['SoftcoreCount'] . " - " . number_format($ownAchievementsObtained['SoftcoreCount'] / $achievementCount * 100, 2, '.', '') . "% <b>(" . $ownAchievementsObtained['HardcoreCount'] . " - " . number_format($ownAchievementsObtained['HardcoreCount'] / $achievementCount * 100, 2, '.', '') . "%)</b>";
            } else {
                echo "0 - 0.00% <b>(0 - 0.00%)</b>";
            }
            echo "</td></tr>";

            // Number of unique achievement obtainers
            echo "<tr><td>Unique Achievement Obtainers:</td><td>" . $uniqueObtainers . "</td></tr>";

            // User who has obtained the most of your achievements
            echo "<tr><td>User Who Obtained the Most Achievements:</td><td>";
            if (count($mostAchievementObtainer) > 0) {
                echo $mostAchievementObtainer['SoftcoreCount'] . " <b>(" . $mostAchievementObtainer['HardcoreCount'] . ")</b> - ";
                echo GetUserAndTooltipDiv($mostAchievementObtainer['User'], true);
                echo GetUserAndTooltipDiv($mostAchievementObtainer['User'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Easiest achievement by retro ratio
            echo "<tr><td>Easiest Achievement by Retro Ratio:</td><td>" . number_format($easiestAchievement['TrueRatio'] / $easiestAchievement['Points'], 2, '.', '') . " - ";
            echo GetAchievementAndTooltipDiv($easiestAchievement['ID'], $easiestAchievement['Title'], $easiestAchievement['Description'], $easiestAchievement['Points'], $easiestAchievement['GameTitle'], $easiestAchievement['BadgeName'], true, false, '', 32);
            echo "</td></tr>";

            // Hardest achievement by retro ratio
            echo "<tr><td>Hardest Achievement by Retro Ratio:</td><td>" . number_format($hardestAchievement['TrueRatio'] / $hardestAchievement['Points'], 2, '.', '') . " - ";
            echo GetAchievementAndTooltipDiv($hardestAchievement['ID'], $hardestAchievement['Title'], $hardestAchievement['Description'], $hardestAchievement['Points'], $hardestAchievement['GameTitle'], $hardestAchievement['BadgeName'], true, false, '', 32);
            echo "</td></tr>";

            // First achievement created
            echo "<tr><td>First Achievement Created:</td><td>" . date("d M, Y H:i", strtotime($firstAchievement['DateCreated'])) . " - ";
            echo GetAchievementAndTooltipDiv($firstAchievement['ID'], $firstAchievement['Title'], $firstAchievement['Description'], $firstAchievement['Points'], $firstAchievement['GameTitle'], $firstAchievement['BadgeName'], true, false, '', 32);
            echo "</td></tr>";

            // Latest achievement created
            echo "<tr><td>Latest Achievement Created:</td><td>" . date("d M, Y H:i", strtotime($lastAchievement['DateCreated'])) . " - ";
            echo GetAchievementAndTooltipDiv($lastAchievement['ID'], $lastAchievement['Title'], $lastAchievement['Description'], $lastAchievement['Points'], $lastAchievement['GameTitle'], $lastAchievement['BadgeName'], true, false, '', 32);
            echo "</td></tr><tr height='10px'></td></tr>";
            echo "</tbody></table>";

            // Recently Obtained achievements
            echo "<table><tbody>";
            echo "</tr><tr><td colspan='4' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Recently Obtained Achievements</td></tr>";
            echo "<tr><td width='34%'>Achievement</td><td width='33%'>Game</td><td width='19%'>User</td><td width='11%'>Date Obtained</td></tr>";
            echo "</tbody></table>";
            echo "<div id='devstatsscrollpane'>";
            echo "<table><tbody>";
            $rowCount = 0;
            for ($i = 0; $i < count($recentlyObtainedAchievements) && $rowCount < ($maxRecentAchievements / 2); $i++) {
                $skipNextEntry = false;
                echo "<tr><td width='35%'>";
                echo GetAchievementAndTooltipDiv($recentlyObtainedAchievements[$i]['AchievementID'], $recentlyObtainedAchievements[$i]['Title'], $recentlyObtainedAchievements[$i]['Description'], $recentlyObtainedAchievements[$i]['Points'], $recentlyObtainedAchievements[$i]['GameTitle'], $recentlyObtainedAchievements[$i]['BadgeName'], true, false, '', 32);

                // Check the next entry for the same achievement ID and time to see if SC and HC were earned at the same time
                // Only display row for Hardcore if so.
                if ($recentlyObtainedAchievements[$i]['User'] == $recentlyObtainedAchievements[$i + 1]['User'] &&
                    $recentlyObtainedAchievements[$i]['Date'] == $recentlyObtainedAchievements[$i + 1]['Date'] &&
                    $recentlyObtainedAchievements[$i]['AchievementID'] == $recentlyObtainedAchievements[$i + 1]['AchievementID']) {
                    echo " <span class='hardcore'>(Hardcore!)</span>";
                    $skipNextEntry = true;
                } elseif ($recentlyObtainedAchievements[$i]['HardcoreMode'] == 1) {
                    echo " <span class='hardcore'>(Hardcore!)</span>";
                }
                echo "</td><td width='35%'>";
                echo GetGameAndTooltipDiv($recentlyObtainedAchievements[$i]['GameID'], $recentlyObtainedAchievements[$i]['GameTitle'], $recentlyObtainedAchievements[$i]['GameIcon'], $recentlyObtainedAchievements[$i]['ConsoleName'], false, 32);
                echo "</td><td width='20%'>";
                echo GetUserAndTooltipDiv($recentlyObtainedAchievements[$i]['User'], true);
                echo GetUserAndTooltipDiv($recentlyObtainedAchievements[$i]['User'], false);
                echo "</td><td width='10%'>";
                echo $recentlyObtainedAchievements[$i]['Date'];
                echo "</td></tr>";

                if ($skipNextEntry) {
                    $i++;
                }
                $rowCount++;
            }
            echo "</tbody></table>";
            echo "</div>";
            echo "</table></tbody>";
            echo "</br></br>";

            /*
             * Code Notes
             */
            echo "<H1>Code Notes</H1>";
            echo "<table><tbody>";

            // Code notes created
            echo "<tr><td width='50%'>Code Notes Created:</td><td>" . $userCodeNoteCount . "</td></tr>";
            echo "</td></tr><tr height='10px'></td></tr>";
            echo "</tbody></table>";

            // Games with user created code notes
            echo "<table><tbody>";
            echo "</tr><tr><td colspan='4' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Games with Code Notes</td></tr>";
            echo "<tr colspan='3'><td width='50%'>Game</td><td width='35%'>Notes Created (% of Total)</td><td>Total Notes</td></tr>";
            echo "</tbody></table>";
            echo "<div id='devstatsscrollpane'>";
            echo "<table><tbody>";
            foreach ($codeNotes as $game) {
                echo "<tr><td width='51%'>";
                echo GetGameAndTooltipDiv($game['GameID'], $game['GameTitle'], $game['GameIcon'], $game['ConsoleName'], false, 32);
                echo "</td><td width='36%'>";
                echo $game['NoteCount'] . " (" . number_format($game['NoteCount'] / $game['TotalNotes'] * 100, 2, '.', '') . "%)";
                echo "</td><td>";
                echo $game['TotalNotes'];
                echo "</td></tr>";
            }
            echo "</tbody></table>";
            echo "</div>";
            echo "</br></br>";

            /*
             * Tickets
             */
            echo "<H1>Tickets</H1>";
            echo "<table><tbody>";

            // Total tickets created
            echo "<tr><td width='50%'>Total Tickets:</td><td>" . $userTickets['total'] . " (" . $userTickets['open'] . " Open - " . $userTickets['closed'] . " Closed - " . $userTickets['resolved'] . " Resolved)</td></tr>";

            // Current ticket ratio
            echo "<tr><td width='50%'>Current Ticket Ratio:</td><td>" . number_format($userTickets['open'] / $achievementCount * 100, 2, '.', '') . "%</td></tr>";

            // Total ticket ratio
            echo "<tr><td width='50%'>Total Ticket Ratio:</td><td>" . number_format($userTickets['total'] / $achievementCount * 100, 2, '.', '') . "%</td></tr>";

            // Tickers per unique achievement
            echo "<tr><td width='50%'>Tickets per Unique Achievements:</td><td>" . $userTickets['uniqueTotal'] . " (" . $userTickets['uniqueOpen'] . " Open - " . $userTickets['uniqueClosed'] . " Closed - " . $userTickets['uniqueResolved'] . " Resolved)</td></tr>";

            // Percent of unique achievements with tickets
            echo "<tr><td width='50%'>Percent of Unique Achievements with Tickets:</td><td>" . number_format($userTickets['uniqueTotal'] / $achievementCount * 100, 2, '.', '') . "%</td></tr>";

            // Game with most tickets
            echo "<tr><td>Game with Most Tickets:</td><td>";
            if ($mostTicketedGame !== null) {
                echo $mostTicketedGame['TicketCount'] . " - ";
                echo GetGameAndTooltipDiv($mostTicketedGame['GameID'], $mostTicketedGame['GameTitle'], $mostTicketedGame['GameIcon'], $mostTicketedGame['ConsoleName'], false, 32);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Achievement with most tickets
            echo "<tr><td>Achievement with Most Tickets:</td><td>";
            if ($mostTicketedAchievement !== null) {
                echo $mostTicketedAchievement['TicketCount'] . " - ";
                echo GetAchievementAndTooltipDiv($mostTicketedAchievement['AchievementID'], $mostTicketedAchievement['AchievementTitle'], $mostTicketedAchievement['AchievementDescription'], $mostTicketedAchievement['AchievementPoints'], $mostTicketedAchievement['GameTitle'], $mostTicketedAchievement['AchievementBadge'], true);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // User who has created the most tickets
            echo "<tr><td>User Who Created the Most Tickets for $dev:</td><td>";
            if ($mostTicketCreator !== null) {
                echo $mostTicketCreator['TicketCount'] . " - ";
                echo GetUserAndTooltipDiv($mostTicketCreator['TicketCreator'], true);
                echo GetUserAndTooltipDiv($mostTicketCreator['TicketCreator'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Tickets closed/resolved for other users
            echo "<tr><td>Tickets Closed/Resolved for Others:</td><td>" . $closedResolvedTicketInfo['Count'] . " (" . $closedResolvedTicketInfo['ClosedCount'] . " Closed - " . $closedResolvedTicketInfo['ResolvedCount'] . " Resolved)</td></tr>";

            // Users you have closed the most tickets for
            echo "<tr><td>User $dev Has Closed the Most Tickets For:</td><td>";
            if ($closedResolvedTicketInfo['ClosedCount'] > 0) {
                echo $closedResolvedTicketInfo['ClosedAuthorCount'] . " - ";
                echo GetUserAndTooltipDiv($closedResolvedTicketInfo['ClosedAuthor'], true);
                echo GetUserAndTooltipDiv($closedResolvedTicketInfo['ClosedAuthor'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Users you have resolved the most tickets for
            echo "<tr><td>User $dev Has Resolved the Most Tickets For:</td><td>";
            if ($closedResolvedTicketInfo['ResolvedCount'] > 0) {
                echo $closedResolvedTicketInfo['ResolvedAuthorCount'] . " - ";
                echo GetUserAndTooltipDiv($closedResolvedTicketInfo['ResolvedAuthor'], true);
                echo GetUserAndTooltipDiv($closedResolvedTicketInfo['ResolvedAuthor'], false);
            } else {
                echo "N/A";
            }
            echo "</td></tr>";

            // Total Ticket Karma and +/-
            echo "<tr><td>Ticket Karma (+/-):</td><td>" . number_format($totalTicketPlusMinusRatio, 2, '.', '');
            if ($totalTicketPlusMinus > 0) {
                echo " (<font color='green'>" . $totalTicketPlusMinus . "</font>)</td></tr>";
            } elseif ($totalTicketPlusMinus < 0) {
                echo " (<font color='red'>" . $totalTicketPlusMinus . "</font>)</td></tr>";
            } else {
                echo " (" . $totalTicketPlusMinus . "</font>)</td></tr>";
            }

            // Closed Ticket Karma and +/-
            echo "<tr><td>Closed Ticket Karma (+/-):</td><td>" . number_format($closedTicketPlusMinusRatio, 2, '.', '');
            if ($closedTicketPlusMinus > 0) {
                echo " (<font color='green'>" . $closedTicketPlusMinus . "</font>)</td></tr>";
            } elseif ($closedTicketPlusMinus < 0) {
                echo " (<font color='red'>" . $closedTicketPlusMinus . "</font>)</td></tr>";
            } else {
                echo " (" . $closedTicketPlusMinus . "</font>)</td></tr>";
            }

            // Resolved Ticket Karma and +/-
            echo "<tr><td>Resolved Ticket Karma (+/-):</td><td>" . number_format($resolvedTicketPlusMinusRatio, 2, '.', '');
            if ($resolvedTicketPlusMinus > 0) {
                echo " (<font color='green'>" . $resolvedTicketPlusMinus . "</font>)</td></tr>";
            } elseif ($resolvedTicketPlusMinus < 0) {
                echo " (<font color='red'>" . $resolvedTicketPlusMinus . "</font>)</td></tr>";
            } else {
                echo " (" . $resolvedTicketPlusMinus . "</font>)</td></tr>";
            }
            echo "</table></tbody>";
        }
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
