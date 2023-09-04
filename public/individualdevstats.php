<?php

use App\Community\Enums\TicketState;
use App\Site\Models\User;

authenticateFromCookie($user, $permissions, $userDetails);

// TODO validate
$dev = requestInputSanitized('u');
if (empty($dev)) {
    return redirect(route('home'));
}

/** @var ?User $devUser */
$devUser = User::firstWhere('User', $dev);
if (!$devUser) {
    abort(404);
}
$dev = $devUser->User; // get case-corrected username

$userArchInfo = getUserAchievementInformation($dev);

// Only get stats if the user has a contribution count
if (empty($userArchInfo)) {
    return redirect(route('user.show', $dev));
}

$userContribCount = $userArchInfo[0]['ContribCount'];
$userContribYield = $userArchInfo[0]['ContribYield'];

// Get sets and achievements per console data for pie charts
$setsPerConsole = getUserSetsPerConsole($dev);
$achievementsPerConsole = getUserAchievementsPerConsole($dev);

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
        if (empty($anyDevGameIDs)) {
            $anyDevHardestGame = $game;
            $anyDevEasiestGame = $game;
        } elseif ($game['MaxPointsAvailable']) {
            if (($anyDevHardestGame['TotalTruePoints'] / $anyDevHardestGame['MaxPointsAvailable']) < ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])) {
                $anyDevHardestGame = $game;
            }
            if ($anyDevEasiestGame['TotalTruePoints'] == 0 || ($anyDevEasiestGame['TotalTruePoints'] / $anyDevEasiestGame['MaxPointsAvailable']) < 1 || ($game['TotalTruePoints'] > 0 && (($anyDevEasiestGame['TotalTruePoints'] / $anyDevEasiestGame['MaxPointsAvailable']) > ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])))) {
                $anyDevEasiestGame = $game;
            }
        }
        $anyDevGameIDs[] = $game['ID'];
        $anyDevRichPresenceCount += $game['RichPresence'];
        $anyDevLeaderboardTotal += $game['NumLBs'];
        if (isset($game['NumLBs'])) {
            $anyDevLeaderboardCount++;
        }

        // Majority developer
        if ($game['MyAchievements'] >= $game['NotMyAchievements']) {
            if (empty($majorityDevGameIDs)) {
                $majorityDevHardestGame = $game;
                $majorityDevEasiestGame = $game;
            } elseif ($game['MaxPointsAvailable']) {
                if (($majorityDevHardestGame['TotalTruePoints'] / $majorityDevHardestGame['MaxPointsAvailable']) < ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])) {
                    $majorityDevHardestGame = $game;
                }
                if ($majorityDevEasiestGame['TotalTruePoints'] == 0 || ($majorityDevEasiestGame['TotalTruePoints'] / $majorityDevEasiestGame['MaxPointsAvailable']) < 1 || ($game['TotalTruePoints'] > 0 && (($majorityDevEasiestGame['TotalTruePoints'] / $majorityDevEasiestGame['MaxPointsAvailable']) > ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])))) {
                    $majorityDevEasiestGame = $game;
                }
            }
            $majorityDevGameIDs[] = $game['ID'];
            $majorityDevAchievementCount += $game['MyAchievements'];
            $majorityDevRichPresenceCount += $game['RichPresence'];
            $majorityDevLeaderboardTotal += $game['NumLBs'];
            if (isset($game['NumLBs'])) {
                $majorityDevLeaderboardCount++;
            }
        }

        // Only developer
        if ($game['MyAchievements'] == $game['NumAchievements']) {
            if (empty($onlyDevGameIDs)) {
                $onlyDevHardestGame = $game;
                $onlyDevEasiestGame = $game;
            } elseif ($game['MaxPointsAvailable']) {
                if (($onlyDevHardestGame['TotalTruePoints'] / $onlyDevHardestGame['MaxPointsAvailable']) < ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])) {
                    $onlyDevHardestGame = $game;
                }
                if ($onlyDevEasiestGame['TotalTruePoints'] == 0 || ($onlyDevEasiestGame['TotalTruePoints'] / $onlyDevEasiestGame['MaxPointsAvailable']) < 1 || ($game['TotalTruePoints'] > 0 && (($onlyDevEasiestGame['TotalTruePoints'] / $onlyDevEasiestGame['MaxPointsAvailable']) > ($game['TotalTruePoints'] / $game['MaxPointsAvailable'])))) {
                    $onlyDevEasiestGame = $game;
                }
            }
            $onlyDevGameIDs[] = $game['ID'];
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
    if (empty($anyDevMostCompletedGame)) {
        if ($game['Completed'] > 0) {
            $anyDevMostCompletedGame = $game;
        }
    } else {
        if ($anyDevMostCompletedGame['Completed'] < $game['Completed']) {
            $anyDevMostCompletedGame = $game;
        }
    }

    if (empty($anyDevMostMasteredGame)) {
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
    if (empty($anyDevUserMostCompleted)) {
        if ($userInfo['Completed'] > 0) {
            $anyDevUserMostCompleted = $userInfo;
        }
    } else {
        if ($anyDevUserMostCompleted['Completed'] < $userInfo['Completed']) {
            $anyDevUserMostCompleted = $userInfo;
        }
    }

    if (empty($anyDevUserMostMastered)) {
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
    if (empty($majorityDevMostCompletedGame)) {
        if ($game['Completed'] > 0) {
            $majorityDevMostCompletedGame = $game;
        }
    } else {
        if ($majorityDevMostCompletedGame['Completed'] < $game['Completed']) {
            $majorityDevMostCompletedGame = $game;
        }
    }

    if (empty($majorityDevMostMasteredGame)) {
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
    if (empty($majorityDevUserMostCompleted)) {
        if ($userInfo['Completed'] > 0) {
            $majorityDevUserMostCompleted = $userInfo;
        }
    } else {
        if ($majorityDevUserMostCompleted['Completed'] < $userInfo['Completed']) {
            $majorityDevUserMostCompleted = $userInfo;
        }
    }

    if (empty($majorityDevUserMostMastered)) {
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
    if (empty($onlyDevMostCompletedGame)) {
        if ($game['Completed'] > 0) {
            $onlyDevMostCompletedGame = $game;
        }
    } else {
        if ($onlyDevMostCompletedGame['Completed'] < $game['Completed']) {
            $onlyDevMostCompletedGame = $game;
        }
    }

    if (empty($onlyDevMostMasteredGame)) {
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
    if (empty($onlyDevUserMostCompleted)) {
        if ($userInfo['Completed'] > 0) {
            $onlyDevUserMostCompleted = $userInfo;
        }
    } else {
        if ($onlyDevUserMostCompleted['Completed'] < $userInfo['Completed']) {
            $onlyDevUserMostCompleted = $userInfo;
        }
    }
    if (empty($onlyDevUserMostMastered)) {
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
        if ($hardestAchievement['Points'] == 0 || $hardestAchievement['Points'] && $achievement['Points'] && ($hardestAchievement['TrueRatio'] / $hardestAchievement['Points']) < ($achievement['TrueRatio'] / $achievement['Points'])) {
            $hardestAchievement = $achievement;
        }
        if ($easiestAchievement['TrueRatio'] == 0 || ($achievement['TrueRatio'] > 0 && ($easiestAchievement['Points'] && $achievement['Points'] && ($easiestAchievement['TrueRatio'] / $easiestAchievement['Points']) > ($achievement['TrueRatio'] / $achievement['Points'])))) {
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
    $achievementIDs[] = $achievement['ID'];
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
$recentlyObtainedAchievements = getRecentUnlocks($achievementIDs, 0, $maxRecentAchievements);

// Initialize code note variables
$mostNotedGame = [];
$userCodeNoteCount = 0;

// Get code note information for user
$codeNotes = getCodeNoteCounts($dev);
foreach ($codeNotes as $game) {
    if (empty($mostNotedGame)) {
        $mostNotedGame = $game;
    }
    $userCodeNoteCount += $game['NoteCount'];
}

// Initialize ticket information variables
$userTickets['total'] = 0;
$userTickets['closed'] = 0;
$userTickets['open'] = 0;
$userTickets['resolved'] = 0;
$userTickets['request'] = 0;
$userTickets['uniqueTotal'] = 0;
$userTickets['uniqueClosed'] = 0;
$userTickets['uniqueOpen'] = 0;
$userTickets['uniqueResolved'] = 0;
$userTickets['uniqueRequest'] = 0;
$userTickets['uniqueValid'] = 0;
$prevID = 0;

// Get ticket information for user
$userTicketInfo = getTicketsForUser($dev);
$counted = false;
foreach ($userTicketInfo as $ticketData) {
    if ($prevID != $ticketData['AchievementID']) { // relies on getTicketsForUser sorting by ID
        $prevID = $ticketData['AchievementID'];
        $userTickets['uniqueTotal']++;
        $counted = false;
    }
    switch ($ticketData['ReportState']) {
        case TicketState::Closed:
            $userTickets['closed'] += $ticketData['TicketCount'];
            $userTickets['total'] += $ticketData['TicketCount'];
            $userTickets['uniqueClosed']++;
            break;
        case TicketState::Open:
            $userTickets['open'] += $ticketData['TicketCount'];
            $userTickets['total'] += $ticketData['TicketCount'];
            $userTickets['uniqueOpen']++;
            if (!$counted) {
                $counted = true;
                $userTickets['uniqueValid']++;
            }
            break;
        case TicketState::Resolved:
            $userTickets['resolved'] += $ticketData['TicketCount'];
            $userTickets['total'] += $ticketData['TicketCount'];
            $userTickets['uniqueResolved']++;
            if (!$counted) {
                $counted = true;
                $userTickets['uniqueValid']++;
            }
            break;
        case TicketState::Request:
            $userTickets['request'] += $ticketData['TicketCount'];
            $userTickets['total'] += $ticketData['TicketCount'];
            $userTickets['uniqueRequest']++;
            break;
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
$closedResolvedTicketInfo['ClosedAuthor'] = '';
$closedResolvedTicketInfo['ClosedAuthorCount'] = 0;
$closedResolvedTicketInfo['ResolvedAuthor'] = '';
$closedResolvedTicketInfo['ResolvedAuthorCount'] = 0;

// Get closed/resolved ticket information
$ticketsClosedResolved = getNumberOfTicketsClosedForOthers($dev);
foreach ($ticketsClosedResolved as $ticketData) {
    // capture the maximum amount of tickets resolved for another user
    if ($closedResolvedTicketInfo['ClosedAuthorCount'] < $ticketData['ClosedCount']) {
        $closedResolvedTicketInfo['ClosedAuthor'] = $ticketData['Author'];
        $closedResolvedTicketInfo['ClosedAuthorCount'] = $ticketData['ClosedCount'];
    }
    if ($closedResolvedTicketInfo['ResolvedAuthorCount'] < $ticketData['ResolvedCount']) {
        $closedResolvedTicketInfo['ResolvedAuthor'] = $ticketData['Author'];
        $closedResolvedTicketInfo['ResolvedAuthorCount'] = $ticketData['ResolvedCount'];
    }

    // tally the data
    $userCount++;
    $closedResolvedTicketInfo['Count'] += $ticketData['TicketCount'];
    $closedResolvedTicketInfo['ClosedCount'] += $ticketData['ClosedCount'];
    $closedResolvedTicketInfo['ResolvedCount'] += $ticketData['ResolvedCount'];
}

$closedResolvedTicketInfo['SelfClosed'] = 0;
$closedResolvedTicketInfo['SelfResolved'] = 0;
$closedResolvedTicketInfo['ClosedByOthers'] = 0;
$closedResolvedTicketInfo['ResolvedByOthers'] = 0;
$ticketsClosedResolved = getNumberOfTicketsClosed($dev);
foreach ($ticketsClosedResolved as $ticketData) {
    if ($ticketData['ResolvedByUser'] === $dev) {
        $closedResolvedTicketInfo['SelfClosed'] = $ticketData['ClosedCount'];
        $closedResolvedTicketInfo['SelfResolved'] = $ticketData['ResolvedCount'];
    } else {
        $closedResolvedTicketInfo['ClosedByOthers'] += $ticketData['ClosedCount'];
        $closedResolvedTicketInfo['ResolvedByOthers'] += $ticketData['ResolvedCount'];
    }
}

$totalTicketPlusMinus = $closedResolvedTicketInfo['SelfResolved'] + $closedResolvedTicketInfo['ResolvedCount'] * 2 - $closedResolvedTicketInfo['ResolvedByOthers'];
$totalTicketPlusMinus = ($totalTicketPlusMinus > 0) ? '+' . $totalTicketPlusMinus : $totalTicketPlusMinus;

RenderContentStart("$dev's Developer Stats");
?>
<script defer src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        google.charts.load('current', { 'packages': ['corechart'] });
        google.charts.setOnLoadCallback(drawChart);
    });

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
                ], JSON_THROW_ON_ERROR);
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
                ], JSON_THROW_ON_ERROR);
            }
            ?>
        ]);

        let chartWidth = 450;
        let chartAreaHeight = '60%';

        /* Render smaller charts on mobile */
        if (window.innerWidth < 640) {
            chartWidth = 300;
            chartAreaHeight = '50%';
        }

        var gameOptions = {
            title: 'Games Developed for Per Console',
            width: chartWidth,
            height: 325,
            chartArea: {
                width: '100%',
                height: chartAreaHeight
            },
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
            colors: ['#000066', '#000099', '#0000cc', '#0000ff', '#3333ff', '#6666ff', '#9999ff'] // blue
            //colors: ['#660000', '#990000', '#cc0000', '#ff0000', '#ff3333', '#ff6666', '#ff9999'] // red
            //colors: ['#003300', '#004d00', '#006600', '#008000', '#009900', '#00b300', '#00cc00'] // green
            //colors: ['#660029', '#99003d', '#cc0052', '#ff0066', '#ff3385', '#ff66a3', '#ff99c2'] // pink
            //colors: ['#333333', '#4d4d4d', '#666666', '#808080', '#999999', '#b3b3b3', '#cccccc'] // B/W
        };

        var achievementOptions = {
            title: 'Achievements Created Per Console',
            width: chartWidth,
            height: 325,
            chartArea: {
                width: '100%',
                height: chartAreaHeight
            },
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
<article>
    <div class="navpath">
        <?php
        echo "<b><a href='/userList.php'>All Users</a> &raquo; <a href='/user/$dev'>$dev</a> &raquo; Developer Stats</b>";
        ?>
    </div>

    <?php if ($user !== null): ?>
        <div class="flex flex-wrap justify-between">
            <div>
            </div>
            <div>
                <form action="individualdevstats.php">
                    <label>
                        Filter by developer:<br>
                        <input size="28" name="u" type="text" value="<?= $dev ?>">
                    </label>
                    <button class="btn">Select</button>
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
        echo "<div class='w-full overflow-hidden text-center'>";
        echo "<div class='inline-block min-h-[325px]' id='gameChart'></div>";
        echo "<div class='inline-block min-h-[325px]' id='achievementChart'></div>";
        echo "</div>";

        /*
         * Games
         */
        echo "<H1>Games</H1>";

        /*
         * Any Development
         */
        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'><td colspan='2' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Any Development</td></tr>";
        echo "<tr></tr><tr class='do-not-highlight'><td colspan='2' align='center'>Stats below are for games that $dev has published at least one achievement for.</td></tr>";

        // Any Development - Games developed for
        echo "<tr><td width='50%'>Games Developed For:</td><td>" . count($anyDevGameIDs) . "</td></tr>";

        // Any Development - Games with Rich Presence
        echo "<tr><td>Games with Rich Presence:</td><td>";
        if (!empty($anyDevGameIDs)) {
            echo $anyDevRichPresenceCount . " - " . number_format($anyDevRichPresenceCount / count($anyDevGameIDs) * 100, 2, '.', '') . "%";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - Games with Leaderboards and Leaderboard count
        echo "<tr><td>Games with Leaderboards:</td><td>";
        if (!empty($anyDevGameIDs)) {
            echo $anyDevLeaderboardCount . " - " . number_format($anyDevLeaderboardCount / count($anyDevGameIDs) * 100, 2, '.', '') . "%</br>" . $anyDevLeaderboardTotal . " Unique Leaderboards";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - Easiest game by retro ratio
        echo "<tr><td>Easiest Game by Retro Ratio:</td><td>";
        if (!empty($anyDevEasiestGame)) {
            echo number_format($anyDevEasiestGame['TotalTruePoints'] / $anyDevEasiestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
            echo gameAvatar($anyDevEasiestGame);
            echo "</br>" . $anyDevEasiestGame['MyAchievements'] . " of " . $anyDevEasiestGame['NumAchievements'] . " Achievements Created";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - Hardest game by retro ratio
        echo "<tr><td>Hardest Game by Retro Ratio</td><td>";
        if (!empty($anyDevHardestGame)) {
            echo number_format($anyDevHardestGame['TotalTruePoints'] / $anyDevHardestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
            echo gameAvatar($anyDevHardestGame);
            echo "</br>" . $anyDevHardestGame['MyAchievements'] . " of " . $anyDevHardestGame['NumAchievements'] . " Achievements Created</br>";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - Complete/Mastered games
        echo "<tr><td>Completed/Mastered Awards:</td><td>";
        if (!empty($anyDevGameIDs)) {
            echo $anyDevCompletedAwards . " <b>(" . $anyDevMasteredAwards . ")</br>";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - Own Complete/Mastered games
        echo "<tr><td>Own Completed/Mastered Awards:</td><td>";
        if (!empty($anyDevOwnAwards)) {
            echo $anyDevOwnAwards['Completed'] . " <b>(" . $anyDevOwnAwards['Mastered'] . ")</br>";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - Most completed game
        echo "<tr><td>Most Completed Game:</td><td>";
        if (!empty($anyDevMostCompletedGame)) {
            echo $anyDevMostCompletedGame['Completed'] . " - ";
            echo gameAvatar($anyDevMostCompletedGame);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - Most mastered game
        echo "<tr><td>Most Mastered Game:</td><td>";
        if (!empty($anyDevMostMasteredGame)) {
            echo $anyDevMostMasteredGame['Mastered'] . " - ";
            echo gameAvatar($anyDevMostMasteredGame);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - User with most completed awards
        echo "<tr><td>User with Most Completed Awards:</td><td>";
        if (!empty($anyDevUserMostCompleted)) {
            echo $anyDevUserMostCompleted['Completed'] . " - ";
            echo userAvatar($anyDevUserMostCompleted['User']);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Any Development - User with most mastered awards
        echo "<tr><td>User with Most Mastered Awards:</td><td>";
        if (!empty($anyDevUserMostMastered)) {
            echo $anyDevUserMostMastered['Mastered'] . " - ";
            echo userAvatar($anyDevUserMostMastered['User']);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";
        echo "</table></tbody>";
        echo "</br>";

        /*
         * Majority Developer
         */
        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'><td colspan='2' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Majority Developer</td></tr>";
        echo "<tr></tr><tr class='do-not-highlight'><td colspan='2' align='center'>Stats below are for games that $dev has published at least half the achievements for.</td></tr>";

        // Majority Developer - Games developed for
        echo "<tr><td width='50%'>Games Developed For:</td><td>" . count($majorityDevGameIDs) . "</td></tr>";

        // Majority Developer - Games with Rich Presence
        echo "<tr><td>Games with Rich Presence:</td><td>";
        if (!empty($majorityDevGameIDs)) {
            echo $majorityDevRichPresenceCount . " - " . number_format($majorityDevRichPresenceCount / count($majorityDevGameIDs) * 100, 2, '.', '') . "%";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - Games with Leaderboards and Leaderboard count
        echo "<tr><td>Games with Leaderboards:</td><td>";
        if (!empty($majorityDevGameIDs)) {
            echo $majorityDevLeaderboardCount . " - " . number_format($majorityDevLeaderboardCount / count($majorityDevGameIDs) * 100, 2, '.', '') . "%</br>" . $majorityDevLeaderboardTotal . " Unique Leaderboards";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - Easiest game by retro ratio
        echo "<tr><td>Easiest Game by Retro Ratio:</td><td>";
        if (!empty($majorityDevEasiestGame)) {
            echo number_format($majorityDevEasiestGame['TotalTruePoints'] / $majorityDevEasiestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
            echo gameAvatar($majorityDevEasiestGame);
            echo "</br>" . $majorityDevEasiestGame['MyAchievements'] . " of " . $majorityDevEasiestGame['NumAchievements'] . " Achievements Created";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - Hardest game by retro ratio
        echo "<tr><td>Hardest Game by Retro Ratio:</td><td>";
        if (!empty($majorityDevHardestGame)) {
            echo number_format($majorityDevHardestGame['TotalTruePoints'] / $majorityDevHardestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
            echo gameAvatar($majorityDevHardestGame);
            echo "</br>" . $majorityDevHardestGame['MyAchievements'] . " of " . $majorityDevHardestGame['NumAchievements'] . " Achievements Created";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - Complete/Mastered games
        echo "<tr><td>Completed/Mastered Awards:</td><td>";
        if (!empty($majorityDevGameIDs)) {
            echo $majorityDevCompletedAwards . " <b>(" . $majorityDevMasteredAwards . ")</br>";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - Own Complete/Mastered games
        echo "<tr><td>Own Completed/Mastered Awards:</td><td>";
        if (!empty($majorityDevOwnAwards)) {
            echo $majorityDevOwnAwards['Completed'] . " <b>(" . $majorityDevOwnAwards['Mastered'] . ")</br>";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - Most completed game
        echo "<tr><td>Most Completed Game:</td><td>";
        if (!empty($majorityDevMostCompletedGame)) {
            echo $majorityDevMostCompletedGame['Completed'] . " - ";
            echo gameAvatar($majorityDevMostCompletedGame);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - Most mastered game
        echo "<tr><td>Most Mastered Game:</td><td>";
        if (!empty($majorityDevMostMasteredGame)) {
            echo $majorityDevMostMasteredGame['Mastered'] . " - ";
            echo gameAvatar($majorityDevMostMasteredGame);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - User with most completed awards
        echo "<tr><td>User with Most Completed Awards:</td><td>";
        if (!empty($majorityDevUserMostCompleted)) {
            echo $majorityDevUserMostCompleted['Completed'] . " - ";
            echo userAvatar($majorityDevUserMostCompleted['User']);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Majority Developer - User with most mastered awards
        echo "<tr><td>User with Most Mastered Awards:</td><td>";
        if (!empty($majorityDevUserMostMastered)) {
            echo $majorityDevUserMostMastered['Mastered'] . " - ";
            echo userAvatar($majorityDevUserMostMastered['User']);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";
        echo "</table></tbody>";
        echo "</br>";

        /*
         * Sole Developer
         */
        echo "<table class='table-highlight'><tbody>";
        echo "<tr class='do-not-highlight'><td colspan='2' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Sole Developer</td></tr>";
        echo "<tr></tr><tr class='do-not-highlight'><td colspan='2' align='center'>Stats below are for games that $dev has published all the achievements for.</td></tr>";

        // Sole Developer - Games developed for
        echo "<tr><td width='50%'>Games Developed For:</td><td>" . count($onlyDevGameIDs) . "</td></tr>";

        // Sole Developer - Games with Rich Presence
        echo "<tr><td>Games with Rich Presence:</td><td>";
        if (!empty($onlyDevGameIDs)) {
            echo $onlyDevRichPresenceCount . " - " . number_format($onlyDevRichPresenceCount / count($onlyDevGameIDs) * 100, 2, '.', '') . "%";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Games with Leaderboards and Leaderboard count
        echo "<tr><td>Games with Leaderboards:</td><td>";
        if (!empty($onlyDevGameIDs)) {
            echo $onlyDevLeaderboardCount . " - " . number_format($onlyDevLeaderboardCount / count($onlyDevGameIDs) * 100, 2, '.', '') . "%</br>" . $onlyDevLeaderboardTotal . " Unique Leaderboards";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Easiest game by retro ratio
        echo "<tr><td>Easiest Game by Retro Ratio:</td><td>";
        if (!empty($onlyDevEasiestGame)) {
            echo number_format($onlyDevEasiestGame['TotalTruePoints'] / $onlyDevEasiestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
            echo gameAvatar($onlyDevEasiestGame);
            echo "</br>" . $onlyDevEasiestGame['MyAchievements'] . " of " . $onlyDevEasiestGame['NumAchievements'] . " Achievements Created";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Hardest game by retro ratio
        echo "<tr><td>Hardest Game by Retro Ratio:</td><td>";
        if (!empty($onlyDevHardestGame)) {
            echo number_format($onlyDevHardestGame['TotalTruePoints'] / $onlyDevHardestGame['MaxPointsAvailable'], 2, '.', '') . " - ";
            echo gameAvatar($onlyDevHardestGame);
            echo "</br>" . $onlyDevHardestGame['MyAchievements'] . " of " . $onlyDevHardestGame['NumAchievements'] . " Achievements Created";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Complete/Mastered games
        echo "<tr><td>Completed/Mastered Awards:</td><td>";
        if (!empty($onlyDevGameIDs)) {
            echo $onlyDevCompletedAwards . " <b>(" . $onlyDevMasteredAwards . ")</br>";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Own Complete/Mastered games
        echo "<tr><td>Own Completed/Mastered Awards:</td><td>";
        if (!empty($onlyDevOwnAwards)) {
            echo $onlyDevOwnAwards['Completed'] . " <b>(" . $onlyDevOwnAwards['Mastered'] . ")</br>";
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Most completed game
        echo "<tr><td>Most Completed Game:</td><td>";
        if (!empty($onlyDevMostCompletedGame)) {
            echo $onlyDevMostCompletedGame['Completed'] . " - ";
            echo gameAvatar($onlyDevMostCompletedGame);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Most mastered game
        echo "<tr><td>Most Mastered Game:</td><td>";
        if (!empty($onlyDevMostMasteredGame)) {
            echo $onlyDevMostMasteredGame['Mastered'] . " - ";
            echo gameAvatar($onlyDevMostMasteredGame);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - User with most completed awards
        echo "<tr><td>User with Most Completed Awards:</td><td>";
        if (!empty($onlyDevUserMostCompleted)) {
            echo $onlyDevUserMostCompleted['Completed'] . " - ";
            echo userAvatar($onlyDevUserMostCompleted['User']);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - User with most mastered awards
        echo "<tr><td>User with Most Mastered Awards:</td><td>";
        if (!empty($onlyDevUserMostMastered)) {
            echo $onlyDevUserMostMastered['Mastered'] . " - ";
            echo userAvatar($onlyDevUserMostMastered['User']);
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
        echo "<table class='table-highlight'><tbody>";

        // Any Development - Achievements created
        echo "<tr><td width='50%'>Achievements Created:</td><td>" . $achievementCount . "</td></tr>";

        // Majority Developer - Achievements created
        echo "<tr><td>Achievements Created (Majority Developer):</td><td>";
        if (!empty($majorityDevGameIDs)) {
            echo $majorityDevAchievementCount;
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Achievements created
        echo "<tr><td>Achievements Created (Sole Developer):</td><td>";
        if (!empty($onlyDevGameIDs)) {
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
        echo achievementAvatar($shortestMemAchievement);
        echo "</td></tr>";

        // Longest achievement by memory length
        echo "<tr><td>Longest Achievement by Memory Length:</td><td>" . $longestMemAchievement['MemLength'] . " - ";
        echo achievementAvatar($longestMemAchievement);
        echo "</td></tr>";

        // Any Development - Average achievement count per game
        echo "<tr><td>Average Achievement Count per Game:</td><td>" . number_format($achievementCount / count($anyDevGameIDs), 2, '.', '') . "</td></tr>";

        // Majority Developer - Average achievement count per game
        echo "<tr><td>Average Achievement Count per Game (Majority Developer):</td><td>";
        if (!empty($majorityDevGameIDs)) {
            echo number_format($majorityDevAchievementCount / count($majorityDevGameIDs), 2, '.', '');
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Sole Developer - Average achievement count per game
        echo "<tr><td>Average Achievement Count per Game (Sole Developer):</td><td>";
        if (!empty($onlyDevGameIDs)) {
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
        if (!empty($mostAchievementObtainer)) {
            echo $mostAchievementObtainer['SoftcoreCount'] . " <b>(" . $mostAchievementObtainer['HardcoreCount'] . ")</b> - ";
            echo userAvatar($mostAchievementObtainer['User']);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Easiest achievement by retro ratio
        echo "<tr><td>Easiest Achievement by Retro Ratio:</td><td>" . number_format($easiestAchievement['TrueRatio'] / $easiestAchievement['Points'], 2, '.', '') . " - ";
        echo achievementAvatar($easiestAchievement);
        echo "</td></tr>";

        // Hardest achievement by retro ratio
        echo "<tr><td>Hardest Achievement by Retro Ratio:</td><td>" . number_format($hardestAchievement['TrueRatio'] / $hardestAchievement['Points'], 2, '.', '') . " - ";
        echo achievementAvatar($hardestAchievement);
        echo "</td></tr>";

        // First achievement created
        echo "<tr><td>First Achievement Created:</td><td>" . date("d M, Y H:i", strtotime($firstAchievement['DateCreated'])) . " - ";
        echo achievementAvatar($firstAchievement);
        echo "</td></tr>";

        // Latest achievement created
        echo "<tr><td>Latest Achievement Created:</td><td>" . date("d M, Y H:i", strtotime($lastAchievement['DateCreated'])) . " - ";
        echo achievementAvatar($lastAchievement);
        echo "</td></tr><tr height='10px'></td></tr>";
        echo "</tbody></table>";

        // Recently Obtained achievements
        echo "<table><tbody>";
        echo "</tr><tr><td colspan='4' align='center' style=\"font-size:24px; padding-top:10px; padding-bottom:10px\">Recently Obtained Achievements</td></tr>";
        echo "<tr><td width='34%'>Achievement</td><td width='33%'>Game</td><td width='19%'>User</td><td width='11%'>Date Obtained</td></tr>";
        echo "</tbody></table>";
        echo "<div id='devstatsscrollpane'>";
        echo "<table class='table-highlight'><tbody>";
        $rowCount = 0;
        $recentlyObtainedAchievementsCount = count($recentlyObtainedAchievements);
        for ($i = 0; $i < $recentlyObtainedAchievementsCount && $rowCount < ($maxRecentAchievements / 2); $i++) {
            $skipNextEntry = false;
            echo "<tr><td width='35%'>";
            echo achievementAvatar($recentlyObtainedAchievements[$i]);

            // Check the next entry for the same achievement ID and time to see if SC and HC were earned at the same time
            // Only display row for Hardcore if so.
            if ($i + 1 < count($recentlyObtainedAchievements)
                && $recentlyObtainedAchievements[$i]['User'] == $recentlyObtainedAchievements[$i + 1]['User']
                && $recentlyObtainedAchievements[$i]['Date'] == $recentlyObtainedAchievements[$i + 1]['Date']
                && $recentlyObtainedAchievements[$i]['AchievementID'] == $recentlyObtainedAchievements[$i + 1]['AchievementID']) {
                echo " (Hardcore!)";
                $skipNextEntry = true;
            } elseif ($recentlyObtainedAchievements[$i]['HardcoreMode'] == 1) {
                echo " (Hardcore!)";
            }

            echo "</td><td width='35%'>";
            echo gameAvatar($recentlyObtainedAchievements[$i]);
            echo "</td><td width='20%'>";
            echo userAvatar($recentlyObtainedAchievements[$i]['User']);
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
        echo "<table class='table-highlight'><tbody>";
        foreach ($codeNotes as $game) {
            echo "<tr><td width='51%'>";
            echo gameAvatar($game);
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
        echo "<table class='table-highlight'><tbody>";

        // Total tickets created
        echo "<tr><td width='50%'>Total Tickets:</td><td>" . $userTickets['total'];
        $parts = [];
        if ($userTickets['open'] > 0) {
            $parts[] = $userTickets['open'] . " Open";
        }
        if ($userTickets['request'] > 0) {
            $parts[] = $userTickets['request'] . " Request";
        }
        if ($userTickets['closed'] > 0) {
            $parts[] = $userTickets['closed'] . " Closed";
        }
        if ($userTickets['resolved'] > 0) {
            $parts[] = $userTickets['resolved'] . " Resolved";
        }
        if (!empty($parts)) {
            echo ' (' . implode(' - ', $parts) . ')';
        }
        echo '</td>';

        // Percent of unique achievements with tickets
        echo "<tr><td width='50%'>Unique Achievements with Open/Resolved Tickets:</td><td>" . $userTickets['uniqueValid'] . ' (';
        $uniqueAchievementTicketRatio = $userTickets['uniqueValid'] / $achievementCount * 100;
        $colored = false;
        if ($uniqueAchievementTicketRatio > 40) {
            echo "<font color='red'>";
            $colored = true;
        } elseif ($uniqueAchievementTicketRatio > 30) {
            echo "<font color='orange'>";
            $colored = true;
        } elseif ($uniqueAchievementTicketRatio < 5) {
            echo "<font color='green'>";
            $colored = true;
        }
        echo number_format($uniqueAchievementTicketRatio, 2, '.', '') . "%";
        if ($colored) {
            echo "</font>";
        }
        echo ")</td></tr>";

        // Game with most tickets
        echo "<tr><td>Game with Most Tickets:</td><td>";
        if ($mostTicketedGame !== null) {
            echo $mostTicketedGame['TicketCount'] . " - ";
            echo gameAvatar($mostTicketedGame);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Achievement with most tickets
        echo "<tr><td>Achievement with Most Tickets:</td><td>";
        if ($mostTicketedAchievement !== null) {
            echo $mostTicketedAchievement['TicketCount'] . " - ";
            echo achievementAvatar($mostTicketedAchievement);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // User who has created the most tickets
        echo "<tr><td>User Who Created the Most Tickets for $dev:</td><td>";
        if ($mostTicketCreator !== null) {
            echo $mostTicketCreator['TicketCount'] . " - ";
            echo userAvatar($mostTicketCreator['TicketCreator']);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        // Tickets resolved for other users
        echo "<tr><td>Tickets Resolved for Others:</td><td>" . $closedResolvedTicketInfo['ResolvedCount'] . "</td></tr>";

        // Users you have resolved the most tickets for
        echo "<tr><td>User $dev Has Resolved the Most Tickets For:</td><td>";
        if ($closedResolvedTicketInfo['ResolvedCount'] > 0) {
            echo $closedResolvedTicketInfo['ResolvedAuthorCount'] . " - ";
            echo userAvatar($closedResolvedTicketInfo['ResolvedAuthor']);
        } else {
            echo "N/A";
        }
        echo "</td></tr>";

        echo "</table></tbody>";
    }
    ?>
</article>
<?php RenderContentEnd(); ?>
