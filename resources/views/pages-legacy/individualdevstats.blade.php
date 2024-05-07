<?php

use App\Community\Enums\TicketState;
use App\Models\User;

authenticateFromCookie($user, $permissions, $userDetails);

// TODO validate
$dev = requestInputSanitized('u');
if (empty($dev)) {
    abort_with(redirect(route('home')));
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
    abort_with(redirect(route('user.show', $dev)));
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
getGamesListByDev($devUser, 0, $gamesList, 1, false);
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
}

$averagePoints = $totalPoints / $achievementCount;
$averageTruePoints = $totalTruePoints / $achievementCount;
$averageMemLength = $totalMemLegth / $achievementCount;

// Get own achievements earned info
$ownAchievementsObtained = getOwnAchievementsObtained($devUser);

// Initialize unique obtainers variables
$uniqueObtainers = 0;
$mostAchievementObtainer = [];

// Get unique obtainers for user
$obtainers = getObtainersOfSpecificUser($devUser);
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

// Initialize code note variables
$userCodeNoteCount = 0;

// Get code note information for user
$codeNotes = collect(getCodeNoteCounts($dev));
$userCodeNoteCount = $codeNotes->sum('NoteCount');

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
?>
<x-app-layout pageTitle="{{ $dev }}'s Developer Stats">
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
    echo "<h1>$dev's Developer Stats</h1>";

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
        echo "<h2>Games</h2>";

        ?>
            {{-- Any development --}}
            <x-developer-game-stats-table
                :easiestGame="$anyDevEasiestGame"
                :hardestGame="$anyDevHardestGame"
                :numGamesWithLeaderboards="$anyDevLeaderboardCount"
                :numGamesWithRichPresence="$anyDevRichPresenceCount"
                :numTotalLeaderboards="$anyDevLeaderboardTotal"
                statsKind="any"
                :targetDeveloperUsername="$dev"
                :targetGameIds="$anyDevGameIDs"
            />

            {{-- Majority development --}}
            <x-developer-game-stats-table
                :easiestGame="$majorityDevEasiestGame"
                :hardestGame="$majorityDevHardestGame"
                :numGamesWithLeaderboards="$majorityDevLeaderboardCount"
                :numGamesWithRichPresence="$majorityDevRichPresenceCount"
                :numTotalLeaderboards="$majorityDevLeaderboardTotal"
                statsKind="majority"
                :targetDeveloperUsername="$dev"
                :targetGameIds="$majorityDevGameIDs"
            />

            {{-- Sole development --}}
            <x-developer-game-stats-table
                :easiestGame="$onlyDevEasiestGame"
                :hardestGame="$onlyDevHardestGame"
                :numGamesWithLeaderboards="$onlyDevLeaderboardCount"
                :numGamesWithRichPresence="$onlyDevRichPresenceCount"
                :numTotalLeaderboards="$onlyDevLeaderboardTotal"
                statsKind="sole"
                :targetDeveloperUsername="$dev"
                :targetGameIds="$onlyDevGameIDs"
            />

        <?php
        /*
         * Achievements
         */
        echo "<h2>Achievements</h2>";
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
        $softcoreCount = $ownAchievementsObtained['SoftcoreCount'] ?? 0;
        $hardcoreCount = $ownAchievementsObtained['HardcoreCount'] ?? 0;
        echo "$softcoreCount - " . number_format($softcoreCount / $achievementCount * 100, 2, '.', '') . "%";
        echo " <b>($hardcoreCount - " . number_format($hardcoreCount / $achievementCount * 100, 2, '.', '') . "%)</b>";
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
        $feedRoute = route('developer.feed', $devUser->User);
        echo <<<HTML
            <p class="text-center text-lg font-semibold my-8">
                View Recently Obtained Achievements in the
                <a href="$feedRoute">Developer Feed</a>
            </p>
        HTML;

        /*
         * Code Notes
         */
        echo "<h2 id='code-notes'>Code Notes</h2>";
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
        echo "<h2>Tickets</h2>";
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
</x-app-layout>
