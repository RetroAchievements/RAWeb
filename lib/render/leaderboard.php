<?php

use RA\Rank;
use RA\UnlockMode;

function GetLeaderboardAndTooltipDiv($lbID, $lbName, $lbDesc, $gameName, $gameIcon, $displayable): string
{
    $tooltipIconSize = 64; // 96;

    sanitize_outputs(
        $lbName,
        $lbDesc,
        $gameName,
        $displayable
    );

    $tooltip = "<div id='objtooltip' style='display:flex;max-width:400px'>";
    $tooltip .= "<img style='margin-right:5px' src='$gameIcon' width='$tooltipIconSize' height='$tooltipIconSize' alt='Game Icons'>";
    $tooltip .= "<div>";
    $tooltip .= "<b>$lbName</b>";
    $tooltip .= "<br>$lbDesc";
    $tooltip .= "<br><br><i>$gameName</i>";
    $tooltip .= "</div>";
    $tooltip .= "</div>";

    $tooltip = tipEscape($tooltip);

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/leaderboardinfo.php?i=$lbID'>" .
        "$displayable" .
        "</a>" .
        "</div>";
}

function RenderGameLeaderboardsComponent($lbData): void
{
    $numLBs = is_countable($lbData) ? count($lbData) : 0;
    echo "<div class='component'>";
    echo "<h3>Leaderboards</h3>";

    if ($numLBs == 0) {
        echo "No leaderboards found: why not suggest some for this game? ";
        echo "<div class='rightalign'><a href='/leaderboardList.php'>Leaderboard List</a></div>";
    } else {
        echo "<table><tbody>";

        $count = 0;
        foreach ($lbData as $lbItem) {
            if ($lbItem['DisplayOrder'] < 0) {
                continue;
            }

            $lbID = $lbItem['LeaderboardID'];
            $lbTitle = $lbItem['Title'];
            $lbDesc = $lbItem['Description'];
            $bestScoreUser = $lbItem['User'];
            $bestScore = $lbItem['Score'];
            $scoreFormat = $lbItem['Format'];

            sanitize_outputs($lbTitle, $lbDesc);

            // Title
            echo "<tr>";
            echo "<td colspan='2'>";
            echo "<div class='fixheightcellsmaller'><a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a></div>";
            echo "<div class='fixheightcellsmaller'>$lbDesc</div>";
            echo "</td>";
            echo "</tr>";

            // Score/Best entry
            echo "<tr class='altdark'>";
            echo "<td>";
            echo GetUserAndTooltipDiv($bestScoreUser, true);
            echo GetUserAndTooltipDiv($bestScoreUser);
            echo "</td>";
            echo "<td>";
            echo "<a href='/leaderboardinfo.php?i=$lbID'>";
            if ($bestScoreUser == '') {
                echo "No entries";
            } else {
                echo GetFormattedLeaderboardEntry($scoreFormat, $bestScore);
            }
            echo "</a>";
            echo "</td>";
            echo "</tr>";

            $count++;
        }

        echo "</tbody></table>";
    }

    // echo "<div class='rightalign'><a href='/forumposthistory.php'>more...</a></div>";

    echo "</div>";
}

/**
 * Renders the friends and global ranking.
 */
function RenderScoreLeaderboardComponent(string $user, bool $friendsOnly, int $numToFetch = 10): void
{
    $lbTypes = [
        "Daily_",
        "Weekly_",
        "AllTime_",
    ];
    $lbNames = [
        "Daily",
        "Weekly",
        "All Time",
    ];
    $friendCount = getFriendCount($user);
    $displayTable = true;
    $currentDate = date("Y-m-d");

    echo "<div id='leaderboard' class='component' >";

    if ($friendsOnly) {
        echo "<h3>Followed Users Ranking</h3>";
        $tabClass = "friendstab";
        if ($friendCount == 0) {
            echo "You don't appear to be following anyone yet. Why not <a href='/userList.php'>browse the user pages</a> to find someone to add to follow?<br>";
            $displayTable = false;
        }
    } else {
        echo "<h3>Global Ranking</h3>";
        $tabClass = "globaltab";
    }

    if ($displayTable) {
        // Create a tab for each of the 5 leaderboard types
        $id = uniqid();
        echo "<div class='tab'>";
        for ($i = 0; $i < count($lbTypes); $i++) {
            if ($i == 0) {
                echo "<button class='" . $tabClass . " active' onclick='tabClick(event, \"" . $lbTypes[$i] . $id . "\", \"" . $tabClass . "\")'>" . $lbNames[$i] . "</button>";
            } else {
                echo "<button class='" . $tabClass . "' onclick='tabClick(event, \"" . $lbTypes[$i] . $id . "\", \"" . $tabClass . "\")'>" . $lbNames[$i] . "</button>";
            }
        }
        echo "</div>";

        // Populate the tabs contents with the leaderboard table
        for ($j = 0; $j < count($lbTypes); $j++) {
            if ($j == 0) {
                echo "<div id='" . $lbTypes[$j] . $id . "' class='tabcontent" . $tabClass . "' style=\"display:block\">";
            } else {
                echo "<div id='" . $lbTypes[$j] . $id . "' class='tabcontent" . $tabClass . "'>";
            }

            if ($friendsOnly) {
                $data = getGlobalRankingData($j, 5, $currentDate, null, $user, 0, 0, $friendCount, 1);
            } else {
                $data = getGlobalRankingData($j, 5, $currentDate, null, null, 0, 0, $numToFetch, 1);
            }

            $rank = 1;
            $userRank = 0;
            $userListed = false;
            $keepAddingRows = true;
            $dateUnix = strtotime($currentDate);
            echo "<table><tbody>";

            // Create table headers
            echo "<tr><th>Rank</th><th>User</th><th>Points</th></tr>";
            foreach ($data as $dataPoint) {
                // Stop adding rows if we hit the number of items to display
                // We still want to continue lopping through the list to get the user rank.
                if ($friendsOnly) {
                    if ($rank == $numToFetch + 1) {
                        $keepAddingRows = false;
                        // Break out if we have already displayed the user
                        if ($userListed) {
                            break;
                        }
                    }
                }

                // Add the table rows
                if ($keepAddingRows) {
                    if ($user !== null && $user == $dataPoint['User']) {
                        echo "<tr style='outline: thin solid'>";
                        $userListed = true;
                    } else {
                        echo "<tr>";
                    }
                    echo "<td class='rank'>" . $rank . "</td>";
                    echo "<td>";
                    echo GetUserAndTooltipDiv($dataPoint['User'], true);
                    echo GetUserAndTooltipDiv($dataPoint['User']);
                    echo "</td>";
                    if ($j == 0) {
                        echo "<td><a href='/historyexamine.php?d=$dateUnix&u=" . $dataPoint['User'] . "'>" .
                            $dataPoint['HardcorePoints'] . "</a>";
                    } else {
                        echo "<td>" . $dataPoint['HardcorePoints'];
                    }
                    echo " <span class='TrueRatio'>(" . $dataPoint['RetroPoints'] . ")</span></td>";
                } else {
                    // Get the users rank among friends then break out since we are not display any more rows
                    if ($user !== null && $user == $dataPoint['User']) {
                        $userRank = $rank;
                        break;
                    }
                }
                $rank++;
            }

            // Display the current user at the bottom of the list if they are not already included
            if ($user !== null && !$userListed) {
                $userData = getGlobalRankingData($j, 5, $currentDate, $user, null, 0, 0, 1, 1);
                if (count($userData) > 0) {
                    echo "<tr><td colspan='3'></td></tr>";
                    echo "<tr style='outline: thin solid'>";

                    if ($j == 2 && !$friendsOnly) {
                        echo "<td class='rank'>" . getUserRank($user, 0) . "</td>";
                    } elseif ($friendsOnly) {
                        echo "<td>" . $userRank . "</td>";
                    } else {
                        echo "<td></td>";
                    }
                    echo "<td>";
                    echo GetUserAndTooltipDiv($userData[0]['User'], true);
                    echo GetUserAndTooltipDiv($userData[0]['User']);
                    echo "</td>";
                    if ($j == 0) {
                        echo "<td><a href='/historyexamine.php?d=$dateUnix&u=" . $userData[0]['User'] . "'>" . $userData[0]['HardcorePoints'] . "</a>";
                    } else {
                        echo "<td>" . $userData[0]['HardcorePoints'];
                    }
                    echo " <span class='TrueRatio'>(" . $userData[0]['RetroPoints'] . ")</span></td>";
                }
            }
            echo "</tbody></table>";

            // Display the more buttons that link to the global ranking page for the specific leaderboard type
            if (!$friendsOnly) {
                echo "<span class='morebutton'><a href='/globalRanking.php?t=" . $j . "'>more...</a></span>";
            } else {
                echo "<span class='morebutton'><a href='/globalRanking.php?t=" . $j . "&f=1'>more...</a></span>";
            }
            echo "</div>";
        }
    }
    echo "</div>";
}

/**
 * Creates the High scores tables on game pages
 */
function RenderTopAchieversComponent($user, array $gameTopAchievers, array $gameLatestMasters): void
{
    echo "<div id='leaderboard' class='component' >";

    $numLatestMasters = count($gameLatestMasters);
    $numTopAchievers = count($gameTopAchievers);
    $masteryThreshold = 10; // Number of masters needed for the "Latest Masters" tab to be selected by default

    echo "<h3>High Scores</h3>";
    echo "<div class='tab'>";
    echo "<button class='scores" . ($numLatestMasters >= $masteryThreshold ? " active" : "") . "' onclick='tabClick(event, \"latestmasters\", \"scores\")'>Latest Masters</button>";
    echo "<button class='scores" . ($numLatestMasters >= $masteryThreshold ? "" : " active") . "' onclick='tabClick(event, \"highscores\", \"scores\")'>High Scores</button>";
    echo "</div>";

    // Latest Masters Tab
    echo "<div id='latestmasters' class='tabcontentscores' style=\"display: " . ($numLatestMasters >= $masteryThreshold ? "block" : "none") . "\">";
    echo "<table class='smalltable'><tbody>";
    echo "<tr><th>Pos</th><th colspan='2' style='max-width:30%'>User</th><th>Mastery Date</th></tr>";

    for ($i = 0; $i < $numLatestMasters; $i++) {
        if (!isset($gameLatestMasters[$i])) {
            continue;
        }

        $nextUser = $gameLatestMasters[$i]['User'];
        $nextLastAward = $gameLatestMasters[$i]['LastAward'];

        // Outline user if they are in the list
        if ($user !== null && $user == $nextUser) {
            echo "<tr style='outline: thin solid'>";
        } else {
            echo "<tr>";
        }

        echo "<td class='rank'>";
        echo $i + 1;
        echo "</td>";

        echo "<td>";
        echo GetUserAndTooltipDiv($nextUser, true);
        echo "</td>";

        echo "<td class='user'>";
        echo GetUserAndTooltipDiv($nextUser, false);
        echo "</td>";

        echo "<td>$nextLastAward</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";

    // High Scores Tab
    echo "<div id='highscores' class='tabcontentscores' style=\"display: " . ($numLatestMasters >= $masteryThreshold ? "none" : "block") . "\">";
    echo "<table><tbody>";
    echo "<tr><th>Pos</th><th colspan='2' style='max-width:30%'>User</th><th>Points</th></tr>";

    for ($i = 0; $i < $numTopAchievers; $i++) {
        if (!isset($gameTopAchievers[$i])) {
            continue;
        }

        $nextUser = $gameTopAchievers[$i]['User'];
        $nextPoints = $gameTopAchievers[$i]['TotalScore'];
        $nextLastAward = $gameTopAchievers[$i]['LastAward'];

        // Outline user if they are in the list
        if ($user !== null && $user == $nextUser) {
            echo "<tr style='outline: thin solid'>";
        } else {
            echo "<tr>";
        }

        echo "<td class='rank'>";
        echo $i + 1;
        echo "</td>";

        echo "<td>";
        echo GetUserAndTooltipDiv($nextUser, true);
        echo "</td>";

        echo "<td class='user'>";
        echo GetUserAndTooltipDiv($nextUser, false);
        echo "</td>";

        echo "<td class='points'>";
        echo "<span class='hoverable' title='Latest awarded at $nextLastAward'>$nextPoints</span>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
    echo "</div>";
}

/**
 * Gets all the global ranking information.
 * This includes User, achievements obtained in hardcore, points, retro points
 * retro ratio, completed awards and mastered awards.
 *
 * Results are configurable based on input parameters, allowing sorting on each of the
 * above stats, returning data for a specific user, returning data for a specific users friends,
 * tracked/untracked filtering, and filtering on a day/week/month/year/all time based on any input date.
 *
 * @param int $lbType Leaderboard timeframe type
 *            0 - Daily
 *            1 - Weekly
 *            2 - Monthly
 *            3 - Yearly
 *            4 - All Time
 * @param int $sort Stats to sort by
 *            1 - User
 *            2 - Total Achievements (no longer supported)
 *            3 - Softcore Achievements (no longer supported)
 *            4 - Hardcore Achievements
 *            5 - Hardcore Points
 *            6 - Retro Points
 *            7 - Retro Ratio
 *            8 - Completed Awards (no longer supported)
 *            9 - Mastered Awards
 * @param string $date Date to grab information from
 * @param string|null $user User to get data for
 * @param string $friendsOf User to get friends data for
 * @param int $untracked Option to include or exclude untracked users
 *            0 - Tracked users only
 *            1 - Untracked users only
 *            2 - Tracked and untracked user
 * @param int $offset starting point to return rows
 * @param int $count number of rows to return
 * @param int $info amount of information to pull from the database
 *            0 - All ranking stats
 *            1 - Just Hardcore Points and Retro Points. Used for the sidebar rankings.
 * @return array Leaderboard data to display
 */
function getGlobalRankingData($lbType, $sort, $date, $user, $friendsOf = null, $untracked = 0, $offset = 0, $count = 50, $info = 0): array
{
    $pointRequirement = "";

    settype($lbType, 'integer');

    $typeCond = match ($lbType) {
        // Daily
        0 => "BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)",
        // Weekly
        1 => "BETWEEN TIMESTAMP(SUBDATE('$date', DAYOFWEEK('$date') - 1)) AND DATE_ADD(DATE_ADD(SUBDATE('$date', DAYOFWEEK('$date') - 1), INTERVAL 6 DAY), INTERVAL 24 * 60 * 60 - 1 SECOND)",
        // Daily by default
        default => "BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)",
    };

    // Set the date names if we are choosing anything but All Time
    $whereDateAchievement = "AND aw.Date";
    $whereDateAward = "AND sa.AwardDate";

    // Determine ascending or descending order
    settype($sort, 'integer');
    if ($sort < 10) {
        $sortOrder = "DESC";
    } else {
        $sortOrder = "ASC";
        $sort = $sort - 10;
    }

    // Determines the condition to get data for single user
    $singleUserAchievementCond = "";
    $singleUserAwardCond = "";
    $singleUserCond = "";
    if ($user !== null) {
        $singleUserAchievementCond = "AND aw.User LIKE '$user'";
        $singleUserAwardCond = "AND sa.User LIKE '$user'";
        $singleUserCond = "AND ua.User LIKE '$user'";
    }

    // Determine the friends condition
    $friendCondAchievement = "";
    $friendCondAward = "";
    $friendCondAllTime = "";
    if ($friendsOf !== null) {
        $friendsSubquery = GetFriendsSubquery($friendsOf);

        $friendCondAchievement = "AND aw.User IN ($friendsSubquery)";
        $friendCondAward = "AND sa.User IN ($friendsSubquery)";
        $friendCondAllTime = "AND ua.User IN ($friendsSubquery)";
    }

    // Determine the ORDER BY condition
    switch ($sort) {
        case 4: // Hardcore Achievements
            $orderCond = "ORDER BY HardcoreCount " . $sortOrder . ", HardcorePoints DESC, User ASC";
            break;
        case 5: // Hardcore Points
            $orderCond = "ORDER BY HardcorePoints " . $sortOrder . ", User ASC";

            // Must have MIN_POINTS hardcore points to show up on All Time Points Sorting
            $pointRequirement = "AND ua.RAPoints >= " . Rank::MIN_POINTS;
            break;
        case 6: // Retro Points
            $orderCond = "ORDER BY RetroPoints " . $sortOrder . ", User ASC";

            // Must have at least MIN_TRUE_POINTS hardcore retro ratio points to show up on All Time Retro Ratio Sorting
            $pointRequirement = "AND ua.TrueRAPoints >= " . Rank::MIN_TRUE_POINTS;
            break;
        case 7: // Retro Ratio
            $orderCond = "ORDER BY RetroRatio " . $sortOrder . ", User ASC";
            break;
        case 9: // Mastered Awards
            $orderCond = "ORDER BY MasteredAwards " . $sortOrder . ", User ASC";
            break;
        default: // Hardcore Points by default
            $orderCond = "ORDER BY HardcorePoints " . $sortOrder . ", User ASC";
            break;
    }

    settype($untracked, 'integer');
    $untrackedCond = match ($untracked) {
        0 => "AND Untracked = 0",
        1 => "AND Untracked = 1",
        default => "",
    };

    // Run the All-Time ranking query
    $retVal = [];
    if ($lbType == 2) {
        if ($info == 0) {
            $selectQuery = "SELECT ua.User,
                    (SELECT COALESCE(SUM(CASE WHEN HardcoreMode = " . UnlockMode::Hardcore . " THEN 1 ELSE 0 END), 0) FROM Awarded AS aw WHERE aw.User = ua.User) AS HardcoreCount,
                    COALESCE(ua.RAPoints, 0) AS HardcorePoints,
                    COALESCE(ua.TrueRAPoints, 0) AS RetroPoints,
                    COALESCE(ROUND(ua.TrueRAPoints/ua.RAPoints, 2), 0) AS RetroRatio ";
        } else {
            $selectQuery = "SELECT ua.User,
                    COALESCE(ua.RAPoints, 0) AS HardcorePoints,
                    COALESCE(ua.TrueRAPoints, 0) AS RetroPoints ";
        }
        $query = "$selectQuery
                    FROM UserAccounts AS ua
                    WHERE TRUE $untrackedCond $singleUserCond $pointRequirement $friendCondAllTime
                    $orderCond, ua.User
                    LIMIT $offset, $count";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $users = [];
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $retVal[] = $db_entry;
                $users[] = $db_entry['User'];
            }

            // Get site award info for each user.
            // This is not ideal but it was the only way I could figure out to accurately get site award information.
            for ($i = 0; $i < count($users); $i++) {
                $query2 = "Select COUNT(*) AS TotalAwards, COALESCE(SUM(CASE WHEN aw.test > 1 THEN 1 ELSE 0 END), 0) AS MasteredAwards
                            FROM (SELECT *, COUNT(AwardData) + AwardDataExtra AS test FROM SiteAwards WHERE User = '" . $users[$i] . "' AND AwardType = 1 GROUP By AwardData order by AwardDataExtra DESC) as aw";
                $dbResult2 = s_mysql_query($query2);
                if ($dbResult2 !== false) {
                    $db_entry2 = mysqli_fetch_assoc($dbResult2);
                    $retVal[$i]['TotalAwards'] = $db_entry2['TotalAwards'];
                    $retVal[$i]['MasteredAwards'] = $db_entry2['MasteredAwards'];
                }
            }
        }
        return $retVal;
    }

    if ($info == 1) {
        $query = "SELECT aw.User AS User,
              SUM(ach.Points) AS HardcorePoints,
              SUM(ach.TrueRatio) AS RetroPoints
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE TRUE $whereDateAchievement $typeCond
              $friendCondAchievement
              $singleUserAchievementCond
              $untrackedCond
              AND HardcoreMode = " . UnlockMode::Hardcore . "
              GROUP BY aw.User
              $orderCond
              LIMIT $offset, $count";
    } else {
        $query = "SELECT User,
              COALESCE(MAX(HardcoreCount), 0) AS HardcoreCount,
              COALESCE(MAX(HardcorePoints), 0) AS HardcorePoints,
              COALESCE(MAX(RetroPoints), 0) AS RetroPoints,
              ROUND(RetroPoints/HardcorePoints, 2) AS RetroRatio,
              COALESCE(MAX(MasteredAwards), 0) AS MasteredAwards
              FROM
                  (
                      (
                          SELECT aw.User AS User,
                          COUNT(ach.ID) AS HardcoreCount,
                          SUM(ach.Points) AS HardcorePoints,
                          SUM(ach.TrueRatio) AS RetroPoints,
                          NULL AS MasteredAwards
                          FROM Awarded AS aw
                          LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                          LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                          WHERE TRUE $whereDateAchievement $typeCond
                          AND HardcoreMode = " . UnlockMode::Hardcore . "
                          $friendCondAchievement
                          $singleUserAchievementCond
                          $untrackedCond
                          GROUP BY aw.User
                      )
                      UNION
                      (
                          SELECT sa.User AS User,
                          NULL AS HardcoreCount,
                          NULL AS HardcorePoints,
                          NULL AS RetroPoints,
                          COALESCE(SUM(CASE WHEN AwardDataExtra = 1 AND AwardType = 1 THEN 1 ELSE 0 END), 0) AS MasteredAwards
                          FROM SiteAwards AS sa
                          LEFT JOIN UserAccounts AS ua ON ua.User = sa.User
                          WHERE TRUE $whereDateAward $typeCond
                          $friendCondAward
                          $singleUserAwardCond
                          $untrackedCond
                          GROUP BY sa.User, sa.AwardData, sa.AwardDate
                      )
                  ) AS Query
              GROUP BY User
              $orderCond
              LIMIT $offset, $count";
    }

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}
