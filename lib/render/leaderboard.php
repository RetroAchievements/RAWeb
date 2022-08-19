<?php

use RA\AwardType;
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
                $data = getGlobalRankingData($j, 5, $currentDate, null, $user, 0, 0, $friendCount + 1, 1);
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
                            $dataPoint['Points'] . "</a>";
                    } else {
                        echo "<td>" . $dataPoint['Points'];
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
                        echo "<td><a href='/historyexamine.php?d=$dateUnix&u=" . $userData[0]['User'] . "'>" . $userData[0]['Points'] . "</a>";
                    } else {
                        echo "<td>" . $userData[0]['Points'];
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
 *            2 - All Time
 * @param int $sort Stats to sort by
 *            1 - User
 *            2 - Softcore Points (used to be Total Achievements)
 *            3 - Softcore Achievements
 *            4 - Hardcore Achievements
 *            5 - Hardcore Points
 *            6 - Retro Points
 *            7 - Retro Ratio
 *            8 - Completed Awards
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
    $unlockMode = UnlockMode::Hardcore;

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
        case 2: // Softcore Points
            $orderCond = "ORDER BY Points " . $sortOrder . ", User ASC";
            $unlockMode = UnlockMode::Softcore;
            break;
        case 3: // Softcore Achievements
            $orderCond = "ORDER BY AchievementCount " . $sortOrder . ", Points DESC, User ASC";
            $unlockMode = UnlockMode::Softcore;
            break;
        case 4: // Hardcore Achievements
            $orderCond = "ORDER BY AchievementCount " . $sortOrder . ", Points DESC, User ASC";
            break;
        default: // Hardcore Points by default
        case 5: // Hardcore Points
            $orderCond = "ORDER BY Points " . $sortOrder . ", User ASC";
            break;
        case 6: // Retro Points
            $orderCond = "ORDER BY RetroPoints " . $sortOrder . ", User ASC";
            break;
        case 7: // Retro Ratio
            $orderCond = "ORDER BY RetroRatio " . $sortOrder . ", User ASC";
            break;
        case 8: // Completed Awards
            $orderCond = "ORDER BY TotalAwards " . $sortOrder . ", User ASC";
            $unlockMode = UnlockMode::Softcore;
            break;
        case 9: // Mastered Awards
            $orderCond = "ORDER BY TotalAwards " . $sortOrder . ", User ASC";
            break;
    }

    settype($untracked, 'integer');
    $untrackedCond = match ($untracked) {
        0 => "AND Untracked = 0",
        1 => "AND Untracked = 1",
        default => "",
    };

    if ($unlockMode == UnlockMode::Hardcore) {
        $totalAwards = "SUM(IF(AwardDataExtra > 0, 1, 0))";
    } else {
        $totalAwards = "COUNT(*)";
        $pointRequirement = "AND ua.RASoftcorePoints >= 0"; // if someone resets a softcore achievement without resetting the hardcore, the query can return negative points
    }

    $retVal = [];
    if ($lbType == 2) { // Run the All-Time ranking query
        if ($friendsOf === null) {
            // if not comparing against friends, only look at the ranked users
            if ($unlockMode == UnlockMode::Softcore) {
                $pointRequirement = "AND ua.RASoftcorePoints >= " . Rank::MIN_POINTS;
            } elseif ($sort == 6) {
                $pointRequirement = "AND ua.TrueRAPoints >= " . Rank::MIN_TRUE_POINTS;
            } else {
                $pointRequirement = "AND ua.RAPoints >= " . Rank::MIN_POINTS;
            }
        }

        if ($info == 0) {
            if ($unlockMode == UnlockMode::Hardcore) {
                $selectQuery = "SELECT ua.User,
                        (SELECT COALESCE(SUM(CASE WHEN HardcoreMode = " . UnlockMode::Hardcore . " THEN 1 ELSE 0 END), 0) FROM Awarded AS aw WHERE aw.User = ua.User) AS AchievementCount,
                        COALESCE(ua.RAPoints, 0) AS Points,
                        COALESCE(ua.TrueRAPoints, 0) AS RetroPoints,
                        COALESCE(ROUND(ua.TrueRAPoints/ua.RAPoints, 2), 0) AS RetroRatio ";
            } else {
                $selectQuery = "SELECT ua.User,
                        (SELECT COALESCE(SUM(CASE WHEN HardcoreMode = " . UnlockMode::Softcore . " THEN 1 ELSE -1 END), 0) FROM Awarded AS aw WHERE aw.User = ua.User) AS AchievementCount,
                        COALESCE(ua.RASoftcorePoints, 0) AS Points,
                        0 AS RetroPoints,
                        0 AS RetroRatio ";
            }
        } else {
            if ($unlockMode == UnlockMode::Hardcore) {
                $selectQuery = "SELECT ua.User,
                        COALESCE(ua.RAPoints, 0) AS Points,
                        COALESCE(ua.TrueRAPoints, 0) AS RetroPoints ";
            } else {
                $selectQuery = "SELECT ua.User,
                        COALESCE(ua.RASoftcorePoints, 0) AS Points,
                        0 AS RetroPoints ";
            }
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
            for ($i = 0; $i < count($users); $i++) {
                $query2 = "SELECT $totalAwards AS TotalAwards FROM SiteAwards WHERE User = '" . $users[$i] . "' AND AwardType = " . AwardType::Mastery;

                $dbResult2 = s_mysql_query($query2);
                if ($dbResult2 !== false) {
                    $db_entry2 = mysqli_fetch_assoc($dbResult2);
                    $retVal[$i]['TotalAwards'] = $db_entry2['TotalAwards'];
                }
            }
        }
        return $retVal;
    }

    if ($info == 1) {
        $query = "SELECT aw.User AS User,
              SUM(ach.Points) AS Points,
              SUM(ach.TrueRatio) AS RetroPoints
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE TRUE $whereDateAchievement $typeCond
              $friendCondAchievement
              $singleUserAchievementCond
              $untrackedCond
              AND HardcoreMode = " . $unlockMode . "
              GROUP BY aw.User
              $orderCond
              LIMIT $offset, $count";
    } else {
        if ($unlockMode == UnlockMode::Hardcore) {
            $achPoints = "CASE WHEN HardcoreMode = " . UnlockMode::Hardcore . " THEN ach.Points ELSE 0 END";
            $achCount = "CASE WHEN HardcoreMode = " . UnlockMode::Hardcore . " THEN 1 ELSE 0 END";
        } else {
            $achPoints = "CASE WHEN HardcoreMode = " . UnlockMode::Softcore . " THEN ach.Points ELSE -ach.Points END";
            $achCount = "CASE WHEN HardcoreMode = " . UnlockMode::Softcore . " THEN 1 ELSE -1 END";
        }

        $query = "SELECT User,
              COALESCE(MAX(AchievementCount), 0) AS AchievementCount,
              COALESCE(MAX(Points), 0) AS Points,
              COALESCE(MAX(RetroPoints), 0) AS RetroPoints,
              ROUND(RetroPoints/Points, 2) AS RetroRatio,
              COALESCE(MAX(TotalAwards), 0) AS TotalAwards
              FROM
                  (
                      (
                          SELECT aw.User AS User,
                          SUM($achCount) AS AchievementCount,
                          SUM($achPoints) as Points,
                          SUM(ach.TrueRatio) AS RetroPoints,
                          NULL AS TotalAwards
                          FROM Awarded AS aw
                          LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                          LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                          WHERE TRUE $whereDateAchievement $typeCond
                          $friendCondAchievement
                          $singleUserAchievementCond
                          $untrackedCond
                          GROUP BY aw.User
                      )
                      UNION
                      (
                          SELECT sa.User AS User,
                          NULL AS AchievementCount,
                          NULL AS Points,
                          NULL AS RetroPoints,
                          $totalAwards AS TotalAwards
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
              HAVING Points > 0 AND AchievementCount > 0
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
