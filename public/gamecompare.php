<?php

use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    abort(401);
}

$gameID = requestInputSanitized('ID', null, 'integer');
$user2 = requestInputSanitized('f');

$totalFriends = getAllFriendsProgress($user, $gameID, $friendScores);

$numAchievements = getGameMetadata($gameID, $user, $achievementData, $gameData, 0, $user2);
if ($gameData === null) {
    abort(404);
}

$consoleID = $gameData['ConsoleID'];
$consoleName = $gameData['ConsoleName'];
$gameTitle = $gameData['Title'];

$gameIcon = $gameData['ImageIcon'];

$gamesPlayedWithAchievements = [];

$numGamesPlayed = getUsersGameList($user, $userGamesList);

foreach ($userGamesList as $nextGameID => $nextGameData) {
    $nextGameTitle = $nextGameData['Title'];
    $nextConsoleName = $nextGameData['ConsoleName'];

    $numAchieved = $nextGameData['NumAchieved'];
    $numPossibleAchievements = $nextGameData['NumAchievements'] ?? 0;
    $gamesPlayedWithAchievements[$nextGameID] = "$nextGameTitle ($nextConsoleName) ($numAchieved / $numPossibleAchievements won)";
}

asort($gamesPlayedWithAchievements);

// Quickly calculate earned/potential
$totalEarned = 0;
$totalPossible = 0;
$numEarned = 0;
if (isset($achievementData)) {
    foreach ($achievementData as &$achievement) {
        /*
         * Some orphaned unlocks might be still around
         */
        $totalPossible += ($achievement['Points'] ?? 0);
        if (isset($achievement['DateAwarded'])) {
            $numEarned++;
            $totalEarned += $achievement['Points'];
        }
    }
}

sanitize_outputs(
    $gameTitle,
    $consoleName,
    $gameIcon,
    $user,
);

RenderContentStart("Game Compare");
?>
<article>
    <div id="gamecompare">
        <?php
        echo "<div class='navpath'>";
        echo renderGameBreadcrumb($gameData);
        echo " &raquo; <b>Game Compare</b>";
        echo "</div>";

        echo "<h3>Game Compare</h3>";

        $pctAwarded = 0;
        if ($numAchievements > 0) {
            $pctAwarded = sprintf("%01.0f", $numEarned * 100.0 / $numAchievements);
        }

        echo gameAvatar($gameData, iconSize: 96);

        if ($numGamesPlayed > 0) {
            echo "<form action='/gamecompare.php'>";
            echo "<input type='hidden' name='f' value='$user2'>";
            echo "<select name='ID'>";
            foreach ($gamesPlayedWithAchievements as $nextGameID => $nextGameTitle) {
                $selected = ($nextGameID == $gameID) ? "SELECTED" : "";
                sanitize_outputs($nextGameTitle);
                echo "<option value='$nextGameID' $selected>$nextGameTitle</option>";
            }
            echo "</select>";
            echo "&nbsp;<button class='btn'>Change Game</button>";
            echo "</form>";
            echo "<br>";
        }

        if ($permissions >= Permissions::Moderator) {
            echo "<div class='devbox mb-3'>";
            echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Admin ▼</span>";
            echo "<div id='devboxcontent' style='display: none'>";

            echo "<div><a class='btn btn-link' href='/usergameactivity.php?ID=$gameID&f=$user2'>View User Game Activity</a></div>";

            echo "</div></div>";
        }

        echo "There are <b>$numAchievements</b> achievements worth <b>$totalPossible</b> points.<br>";

        $iconSize = 48;

        echo "<table class='table-highlight'><tbody>";

        echo "<tr class='do-not-highlight'>";

        echo "<th class='text-right'>";
        echo "<a href='/user/$user'>$user</a><br>";
        echo userAvatar($user, label: false, iconSize: $iconSize, iconClass: 'rounded-sm');
        echo "</th>";

        echo "<th><center>Achievement</center></th>";

        echo "<th>";
        echo "<a href='/user/$user2'>$user2</a><br>";
        echo userAvatar($user2, label: false, iconSize: $iconSize, iconClass: 'rounded-sm');
        echo "</th>";

        echo "</tr>";

        $leftHardcoreAwardedCount = 0;
        $rightHardcoreAwardedCount = 0;
        $leftHardcoreAwardedPoints = 0;
        $rightHardcoreAwardedPoints = 0;
        $leftSoftcoreAwardedCount = 0;
        $rightSoftcoreAwardedCount = 0;
        $leftSoftcoreAwardedPoints = 0;
        $rightSoftcoreAwardedPoints = 0;
        $maxPoints = 0;

        $achIter = 0;
        foreach ($achievementData as $nextAch) {
            /*
             * Some orphaned unlocks might be still around
             */
            if (!isset($nextAch['ID'])) {
                continue;
            }

            echo "<tr>";

            $achID = $nextAch['ID'];
            $achTitle = $nextAch['Title'];
            $achDesc = $nextAch['Description'];
            $achPoints = $nextAch['Points'];

            sanitize_outputs($achTitle, $achDesc);

            $maxPoints += $achPoints;

            $badgeName = $nextAch['BadgeName'];

            $awardedLeft = $nextAch['DateEarned'] ?? null;
            $awardedRight = $nextAch['DateEarnedFriend'] ?? null;
            $awardedHCLeft = $nextAch['DateEarnedHardcore'] ?? null;
            $awardedHCRight = $nextAch['DateEarnedFriendHardcore'] ?? null;

            echo "<td>";
            if (isset($awardedLeft)) {
                echo "<div class='flex justify-between gap-2'>";
                echo achievementAvatar($nextAch, label: false, iconSize: $iconSize, iconClass: isset($awardedHCLeft) ? 'goldimage' : '', tooltip: false);
                if (isset($awardedHCLeft)) {
                    $leftHardcoreAwardedCount++;
                    $leftHardcoreAwardedPoints += $achPoints;
                    echo "<small class='smalldate'>$awardedHCLeft<br>HARDCORE</small>";
                } else {
                    $leftSoftcoreAwardedCount++;
                    $leftSoftcoreAwardedPoints += $achPoints;
                    echo "<small class='smalldate'>$awardedLeft</small>";
                }
                echo "</div>";
            } else {
                echo achievementAvatar($nextAch, label: false, icon: $badgeName . "_lock", iconSize: $iconSize, tooltip: false);
            }
            echo "</td>";

            echo "<td class='w-[250px]'>";
            echo "<p class='embedded'>";
            echo "<a href=\"achievement/$achID\"><strong>$achTitle</strong></a><br>";
            echo "$achDesc<br>";
            echo "($achPoints Points)";
            echo "</p>";
            echo "</td>";

            echo "<td>";
            if (isset($awardedRight)) {
                echo "<div class='flex justify-between gap-2'>";
                if (isset($awardedHCRight)) {
                    $rightHardcoreAwardedCount++;
                    $rightHardcoreAwardedPoints += $achPoints;
                    echo "<small class='smalldate'>$awardedHCRight<br>HARDCORE</small>";
                } else {
                    $rightSoftcoreAwardedCount++;
                    $rightSoftcoreAwardedPoints += $achPoints;
                    echo "<small class='smalldate'>$awardedRight</small>";
                }
                echo achievementAvatar($nextAch, label: false, icon: $badgeName, iconSize: $iconSize, iconClass: isset($awardedHCRight) ? 'goldimage' : '', tooltip: false);
                echo "</div>";
            } else {
                echo "<div class='text-right'>";
                echo achievementAvatar($nextAch, label: false, icon: $badgeName . "_lock", iconSize: $iconSize, tooltip: false);
                echo "</div>";
            }
            echo "</td>";

            echo "</tr>";
        }

        // Repeat user images:
        echo "<tr class='do-not-highlight'>";

        echo "<td>";
        echo "<div class='text-right'>";
        echo userAvatar($user, label: false, iconSize: $iconSize, iconClass: 'rounded-sm');
        echo "</div>";
        echo "</td>";

        echo "<td></td>";

        echo "<td>";
        echo "<div>";
        echo userAvatar($user2, label: false, iconSize: $iconSize, iconClass: 'rounded-sm');
        echo "</div>";
        echo "</td>";

        echo "</tr>";

        // Draw totals:
        echo "<tr class='do-not-highlight'>";
        echo "<td class='float-right'>";
        echo "<b>$leftHardcoreAwardedCount</b>/$numAchievements unlocked<br><b>$leftHardcoreAwardedPoints</b>/$maxPoints points";
        echo "</td>";
        echo "<td></td>";
        echo "<td>";
        echo "<b>$rightHardcoreAwardedCount</b>/$numAchievements unlocked<br><b>$rightHardcoreAwardedPoints</b>/$maxPoints points";
        echo "</td>";
        echo "</tr>";
        if ($leftSoftcoreAwardedCount > 0 || $rightSoftcoreAwardedCount > 0) {
            echo "<tr class='do-not-highlight'>";
            if ($leftSoftcoreAwardedCount > 0) {
                echo "<td class='float-right'>";
                echo "<span class='text-muted'<b>$leftSoftcoreAwardedCount</b>/$numAchievements unlocked<br><b>$leftSoftcoreAwardedPoints</b>/$maxPoints points</span></td>";
            } else {
                echo "<td class='float-right'></td>";
            }
            echo "<td></td>";
            if ($rightSoftcoreAwardedCount > 0) {
                echo "<td>";
                echo "<span class='text-muted'<b>$rightSoftcoreAwardedCount</b>/$numAchievements unlocked<br><b>$rightSoftcoreAwardedPoints</b>/$maxPoints points</span></td>";
            } else {
                echo "<td></td>";
            }
            echo "</tr>";
        }

        echo "</tbody></table>";

        echo "<br><br>";
        ?>
    </div>
</article>
<?php view()->share('sidebar', true) ?>
<aside>
    <?php RenderGameCompare($user, $gameID, $friendScores, $totalPossible); ?>
</aside>
<?php RenderContentEnd(); ?>
