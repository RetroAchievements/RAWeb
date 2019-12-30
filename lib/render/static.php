<?php
function RenderStaticDataComponent($staticData)
{
    echo "<div class='component statistics'>";
    echo "<h3>Statistics</h3>";

    $numGames = $staticData['NumGames'];
    $numAchievements = $staticData['NumAchievements'];
    $numAwarded = $staticData['NumAwarded'];
    $numRegisteredPlayers = $staticData['NumRegisteredUsers'];

    $avAwardedPerPlayer = 0;
    if ($numRegisteredPlayers > 0) {
        $avAwardedPerPlayer = sprintf("%1.2f", ($numAwarded / $numRegisteredPlayers));
    }

    $lastRegisteredUser = $staticData['LastRegisteredUser'];
    $lastRegisteredUserAt = $staticData['LastRegisteredUserAt'];
    $lastAchievementEarnedID = $staticData['LastAchievementEarnedID'];
    $lastAchievementEarnedTitle = $staticData['LastAchievementEarnedTitle'];
    $lastAchievementEarnedByUser = $staticData['LastAchievementEarnedByUser'];
    $lastAchievementEarnedAt = $staticData['LastAchievementEarnedAt'];
    $totalPointsEarned = $staticData['TotalPointsEarned'];

    $nextGameToScanID = $staticData['NextGameToScan'];
    $nextGameToScan = $staticData['NextGameTitleToScan'];
    $nextGameToScanIcon = $staticData['NextGameToScanIcon'];
    $nextGameConsoleToScan = $staticData['NextGameToScanConsole'];

    $nextUserToScan = $staticData['NextUserToScan'];

    $niceRegisteredAt = date("d M\nH:i", strtotime($lastRegisteredUserAt));

    if ($lastRegisteredUser == null) {
        $lastRegisteredUser = "unknown";
    }

    if ($lastRegisteredUserAt == null) {
        $lastRegisteredUserAt = "unknown";
    }

    echo "<div class='infobox'>";
    echo "There are ";
    echo "<a title='Achievement List' href='/gameList.php?s=2'>$numAchievements</a>";
    echo " achievements registered for ";
    echo "<a title='Game List' href='/gameList.php?s=1'>$numGames</a> games. ";

    echo "<a title='Achievement List' href='/achievementList.php'>$numAwarded</a>";
    echo " achievements have been awarded to the ";
    echo "<a title='User List' href='/userList.php'>$numRegisteredPlayers</a>";
    echo " registered players (average: $avAwardedPerPlayer per player)<br>";

    echo "<br>";

    echo "Since 2nd March 2013, a total of ";
    echo "<span title='Awesome!'><strong>$totalPointsEarned</strong></span>";
    echo " points have been earned by users on RetroAchievements.org.<br>";

    echo "<br>";

    echo "The last registered user was ";
    echo GetUserAndTooltipDiv( $lastRegisteredUser, FALSE );
    //echo "<a href='/User/$lastRegisteredUser'>$lastRegisteredUser</a>";
    echo " on $niceRegisteredAt.<br>";

    //echo "<br>";
    //echo "Next game to scan: ";
    //echo GetGameAndTooltipDiv( $nextGameToScanID, $nextGameToScan, $nextGameToScanIcon, $nextGameConsoleToScan, FALSE, 32, TRUE );
    //echo "<br>";
    //echo "Next user to scan: ";
    //echo GetUserAndTooltipDiv( $nextUserToScan, FALSE );
    //echo "The last achievement earned was ";
    //echo "<a href='/Achievement/$lastAchievementEarnedID'>$lastAchievementEarnedTitle</a>";
    //echo " by ";
    //echo "<a href='/User/$lastAchievementEarnedByUser'>$lastAchievementEarnedByUser</a><br>";

    echo "</div>";

    //var_dump( $staticData );

    echo "</div>";
}
