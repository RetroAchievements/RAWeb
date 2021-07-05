<?php

function GetLeaderboardAndTooltipDiv($lbID, $lbName, $lbDesc, $gameName, $gameIcon, $displayable): string
{
    $tooltipIconSize = 64; //96;

    $lbNameStr = str_replace("'", "\'", $lbName);
    $lbDescStr = str_replace("'", "\'", $lbDesc);
    $gameNameStr = str_replace("'", "\'", $gameName);

    $tooltip = "<div id=\'objtooltip\'>" .
        "<img src=\'$gameIcon\' width=\'$tooltipIconSize\' height=\'$tooltipIconSize\ />" .
        "<b>$lbNameStr</b><br>" .
        "<i>($gameNameStr)</i><br>" .
        "<br>" .
        "$lbDescStr<br>" .
        "</div>";

    $tooltip = str_replace('<', '&lt;', $tooltip);
    $tooltip = str_replace('>', '&gt;', $tooltip);

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/leaderboardinfo.php?i=$lbID'>" .
        "$displayable" .
        "</a>" .
        "</div>";
}

function RenderGameLeaderboardsComponent($gameID, $lbData)
{
    $numLBs = count($lbData);
    echo "<div class='component'>";
    echo "<h3>Leaderboards</h3>";

    if ($numLBs == 0) {
        echo "No leaderboards found: why not suggest some for this game? ";
        echo "<div class='rightalign'><a href='/leaderboardList.php'>Leaderboard List</a></div>";
    } else {
        echo "<table><tbody>";

        $count = 0;
        foreach ($lbData as $lbItem) {
            $lbID = $lbItem['LeaderboardID'];
            $lbTitle = $lbItem['Title'];
            $lbDesc = $lbItem['Description'];
            $bestScoreUser = $lbItem['User'];
            $bestScore = $lbItem['Score'];
            $scoreFormat = $lbItem['Format'];

            sanitize_outputs($lbTitle, $lbDesc);

            //    Title
            echo "<tr>";
            echo "<td colspan='2'>";
            echo "<div class='fixheightcellsmaller'><a href='/leaderboardinfo.php?i=$lbID'>$lbTitle</a></div>";
            echo "<div class='fixheightcellsmaller'>$lbDesc</div>";
            echo "</td>";
            echo "</tr>";

            //    Score/Best entry
            echo "<tr class='altdark'>";
            echo "<td>";
            //echo "<a href='/user/" . $bestScoreUser . "'><img alt='$bestScoreUser' title='$bestScoreUser' src='/UserPic/$bestScoreUser.png' width='32' height='32' /></a>";
            echo GetUserAndTooltipDiv($bestScoreUser, true);
            echo GetUserAndTooltipDiv($bestScoreUser, false);
            echo "</td>";
            echo "<td>";
            echo "<a href='/leaderboardinfo.php?i=$lbID'>";
            echo GetFormattedLeaderboardEntry($scoreFormat, $bestScore);
            echo "</a>";
            echo "</td>";

            echo "</tr>";

            $count++;
        }

        echo "</tbody></table>";
    }

    //echo "<div class='rightalign'><a href='/forumposthistory.php'>more...</a></div>";

    echo "</div>";
}

/**
 * Renders the friends and global ranking.
 *
 * @param string $user to render leaderboard for
 * @param bool $friendsOnly render friends leaderboard
 * @param int $numToFetch number if entries to show in the leaderboard
 */
function RenderScoreLeaderboardComponent($user, $friendsOnly, $numToFetch = 10)
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

    if ($friendsOnly == true) {
        echo "<h3>Friends Ranking</h3>";
        $tabClass = "friendstab";
        if ($friendCount == 0) {
            echo "You don't appear to have friends registered here yet. Why not leave a comment on the <a href='/forum.php'>forums</a> or <a href='/userList.php'>browse the user pages</a> to find someone to add to your friend list?<br>";
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

            if ($friendsOnly == true) {
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
                if ($friendsOnly == true) {
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
                    echo GetUserAndTooltipDiv($dataPoint['User'], false);
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
            if ($user !== null && $userListed == false) {
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
                    echo GetUserAndTooltipDiv($userData[0]['User'], false);
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
            if ($friendsOnly == false) {
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
 *
 * @param string $user the logged in user
 * @param array $gameTopAchievers top 10 highest scoreres for the game
 * @param array $gameLatestMasters top 10 latest masters for the game
 */
function RenderTopAchieversComponent($user, $gameTopAchievers, $gameLatestMasters)
{
    echo "<div id='leaderboard' class='component' >";

    $numLatestMasters = count($gameLatestMasters);
    $numTopAchievers = count($gameTopAchievers);
    $masteryThreshold = 10; //Number of masters needed for the "Latest Masters" tab to be selected by default

    echo "<h3>High Scores</h3>";
    echo "<div class='tab'>";
    echo "<button class='scores" . ($numLatestMasters >= $masteryThreshold ? " active" : "") . "' onclick='tabClick(event, \"latestmasters\", \"scores\")'>Latest Masters</button>";
    echo "<button class='scores" . ($numLatestMasters >= $masteryThreshold ? "" : " active") . "' onclick='tabClick(event, \"highscores\", \"scores\")'>High Scores</button>";
    echo "</div>";

    //Latest Masters Tab
    echo "<div id='latestmasters' class='tabcontentscores' style=\"display: " . ($numLatestMasters >= $masteryThreshold ? "block" : "none") . "\">";
    echo "<table class='smalltable'><tbody>";
    echo "<tr><th>Pos</th><th colspan='2' style='max-width:30%'>User</th><th>Mastery Date</th></tr>";

    for ($i = 0; $i < $numLatestMasters; $i++) {
        if (!isset($gameLatestMasters[$i])) {
            continue;
        }

        $nextUser = $gameLatestMasters[$i]['User'];
        $nextLastAward = $gameLatestMasters[$i]['LastAward'];

        //Outline user if they are in the list
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

    //High Scores Tab
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

        //Outline user if they are in the list
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
 * This includes User, acheivements obtained (softcore and hardcore), points, retro points
 * retro ratio, completed awards and mastered awards.
 *
 * Results are configurable based on input parameters, allowing sorting on each of the
 * abobe stats, returning data for a specific user, returning data for a specific users friends,
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
 *            2 - Total Achievement
 *            3 - Softcore Achievements
 *            4 - Hardcore Achievements
 *            5 - Points
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
 *            1 - Just Points and Retro Points. Used for the sidebar rankings.
 * @return array Leaderboard data to display
 */
function getGlobalRankingData($lbType, $sort, $date, $user, $friendsOf = null, $untracked = 0, $offset = 0, $count = 50, $info = 0)
{
    $pointRequirement = "";

    // Determine the WHERE condition
    switch ($lbType) {
        case 0: // Daily
            $whereCond = "BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)";
            break;
        case 1: // Weekly
            $whereCond = "BETWEEN TIMESTAMP(SUBDATE('$date', DAYOFWEEK('$date') - 1)) AND DATE_ADD(DATE_ADD(SUBDATE('$date', DAYOFWEEK('$date') - 1), INTERVAL 6 DAY), INTERVAL 24 * 60 * 60 - 1 SECOND)";
            break;
        default: // Daily by default
            $whereCond = "BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)";
            break;
    }

    // Set the date names if we are choosing anything but All Time
    $whereDateAchievement = "";
    $whereDateAward = "";
    if (mb_strlen($whereCond) > 0) {
        $whereDateAchievement = "AND aw.Date";
        $whereDateAward = "AND sa.AwardDate";
    }

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
        $friendCondAchievement = "AND (aw.User IN (SELECT Friend FROM Friends WHERE User LIKE '$friendsOf' AND Friendship = 1) OR aw.User LIKE '$friendsOf')";
        $friendCondAward = "AND (sa.User IN (SELECT Friend FROM Friends WHERE User LIKE '$friendsOf' AND Friendship = 1) OR sa.User LIKE '$friendsOf')";
        $friendCondAllTime = "AND (ua.User IN (SELECT Friend FROM Friends WHERE User LIKE '$friendsOf' AND Friendship = 1) OR ua.User LIKE '$friendsOf')";
    }

    // Determine the ORDER BY condition
    switch ($sort) {
        case 2: // Total Achievements
            $orderCond = "ORDER BY AchievementsObtained " . $sortOrder . ", Points DESC, User ASC";
            break;
        case 3: // Softcore Achievements
            $orderCond = "ORDER BY SoftcoreCount " . $sortOrder . ", Points DESC, User ASC";
            break;
        case 4: // Hardcore Achievements
            $orderCond = "ORDER BY HardcoreCount " . $sortOrder . ", Points DESC, User ASC";
            break;
        case 5: // Points
            $orderCond = "ORDER BY Points " . $sortOrder . ", User ASC";
            break;
        case 6: // Retro Points
            $orderCond = "ORDER BY RetroPoints " . $sortOrder . ", User ASC";
            break;
        case 7: // Retro Ratio
            $orderCond = "ORDER BY RetroRatio " . $sortOrder . ", User ASC";

            // Must have higher than 2500 points to show up on All Time Retro Ratio Sorting
            $pointRequirement = "AND ua.RAPoints > 2500";
            break;
        case 8: // Completed Awards
            $orderCond = "ORDER BY CompletedAwards " . $sortOrder . ", User ASC";
            break;
        case 9: // Mastered Awards
            $orderCond = "ORDER BY MasteredAwards " . $sortOrder . ", User ASC";
            break;
        default: // Points by default
            $orderCond = "ORDER BY Points " . $sortOrder . ", User ASC";
            break;
    }

    // Determine the untracked user condition
    switch ($untracked) {
        case 0: // Get tracked only
            $untrackedCond = "AND Untracked = 0";
            break;
        case 1: // Get untracked only
            $untrackedCond = "AND Untracked = 1";
            break;
        default: // Get both
            $untrackedCond = "";
            break;
    }

    // Run the All Time ranking query
    $retVal = [];
    if ($lbType == 2) {
        if ($info == 0) {
            $selectQuery = "SELECT ua.User,
                    (SELECT COALESCE(SUM(CASE WHEN HardcoreMode = 0 THEN 1 ELSE 0 END), 0) FROM Awarded AS aw WHERE aw.User = ua.User) AS SoftcoreCount,
                    (SELECT COALESCE(SUM(CASE WHEN HardcoreMode = 1 THEN 1 ELSE 0 END), 0) FROM Awarded AS aw WHERE aw.User = ua.User) AS HardcoreCount,
                    (SELECT COUNT(*) FROM Awarded AS aw WHERE aw.User = ua.User) AS AchievementsObtained,
                    COALESCE(ua.RAPoints, 0) AS Points,
                    COALESCE(ua.TrueRAPoints, 0) AS RetroPoints,
                    COALESCE(ROUND(ua.TrueRAPoints/ua.RAPoints, 2), 0) AS RetroRatio ";
        } else {
            $selectQuery = "SELECT ua.User,
                    COALESCE(ua.RAPoints, 0) AS Points,
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
                $query2 = "Select COUNT(*) AS TotalAwards, COALESCE(SUM(CASE WHEN aw.test > 1 THEN 1 ELSE 0 END), 0) AS MasteredAwards, COALESCE(SUM(CASE WHEN aw.test <= 1 THEN 1 ELSE 0 END), 0) AS CompletedAwards
                            FROM (SELECT *, COUNT(AwardData) + AwardDataExtra AS test FROM SiteAwards WHERE User = '" . $users[$i] . "' AND AwardType = 1 GROUP By AwardData order by AwardDataExtra DESC) as aw";
                $dbResult2 = s_mysql_query($query2);
                if ($dbResult2 !== false) {
                    $db_entry2 = mysqli_fetch_assoc($dbResult2);
                    $retVal[$i]['TotalAwards'] = $db_entry2['TotalAwards'];
                    $retVal[$i]['CompletedAwards'] = $db_entry2['CompletedAwards'];
                    $retVal[$i]['MasteredAwards'] = $db_entry2['MasteredAwards'];
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
              WHERE TRUE $whereDateAchievement $whereCond
              $friendCondAchievement
              $singleUserAchievementCond
              $untrackedCond
              GROUP BY aw.User
              $orderCond
              LIMIT $offset, $count";
    } else {
        $query = "SELECT User,
              COALESCE(MAX(SoftcoreCount), 0) AS SoftcoreCount,
              COALESCE(MAX(HardcoreCount), 0) AS HardcoreCount,
              COALESCE(MAX(AchievementsObtained), 0) AS AchievementsObtained,
              COALESCE(MAX(Points), 0) AS Points,
              COALESCE(MAX(RetroPoints), 0) AS RetroPoints,
              ROUND(RetroPoints/Points, 2) AS RetroRatio,
              COALESCE(MAX(CompletedAwards), 0) AS CompletedAwards,
              COALESCE(MAX(MasteredAwards), 0) AS MasteredAwards,
              COALESCE(MAX(CompletedAwards), 0) + COALESCE(MAX(MasteredAwards), 0) AS TotalAwards
              FROM
                  (
                      (
                          SELECT aw.User AS User,
                          COALESCE(SUM(CASE WHEN HardcoreMode = 0 THEN 1 ELSE 0 END), 0) AS SoftcoreCount,
                          COALESCE(SUM(CASE WHEN HardcoreMode = 1 THEN 1 ELSE 0 END), 0) AS HardcoreCount,
                          COUNT(*) AS AchievementsObtained,
                          SUM(ach.Points) AS Points,
                          SUM(ach.TrueRatio) AS RetroPoints,
                          NULL AS CompletedAwards,
                          NULL AS MasteredAwards
                          FROM Awarded AS aw
                          LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                          LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                          WHERE TRUE $whereDateAchievement $whereCond
                          $friendCondAchievement
                          $singleUserAchievementCond
                          $untrackedCond
                          GROUP BY aw.User
                      )
                      UNION
                      (
                          SELECT sa.User AS User,
                          NULL AS SoftcoreCount,
                          NULL AS HardcoreCount,
                          NULL AS AchievementsObtained,
                          NULL AS Points,
                          NULL AS RetroPoints,
                          COALESCE(SUM(CASE WHEN AwardDataExtra = 0 AND AwardType = 1 THEN 1 ELSE 0 END), 0) AS CompletedAwards,
                          COALESCE(SUM(CASE WHEN AwardDataExtra = 1 AND AwardType = 1 THEN 1 ELSE 0 END), 0) AS MasteredAwards
                          FROM SiteAwards AS sa
                          LEFT JOIN UserAccounts AS ua ON ua.User = sa.User
                          WHERE TRUE $whereDateAward $whereCond
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

/**
 * Gets completed and mastery award information.
 * This includes User, Game and Completed or Mastered Date.
 *
 * Results are configurable based on input parameters allowing returning data for a specific users friends
 * and selecting a specific date
 *
 * @param string $date Date to grab information from
 * @param string|null $user User to get data for
 * @param string $friendsOf User to get friends data for
 * @param int $offset starting point to return rows
 * @param int $count number of rows to return
 * @return array Leaderboard data to display
 */
function getRecentMasteryData($date, $user, $friendsOf = null, $offset = 0, $count = 50)
{
    // Determine the friends condition
    $friendCondAward = "";
    if ($friendsOf !== null) {
        $friendCondAward = "AND (saw.User IN (SELECT Friend FROM Friends WHERE User LIKE '$friendsOf' AND Friendship = 1) OR saw.User LIKE '$friendsOf')";
    }

    $retVal = [];
    $query = "SELECT saw.User, saw.AwardDate as AwardedAt, UNIX_TIMESTAMP( saw.AwardDate ) as AwardedAtUnix, saw.AwardType, saw.AwardData, saw.AwardDataExtra, gd.Title AS GameTitle, gd.ID AS GameID, c.Name AS ConsoleName, gd.ImageIcon AS GameIcon
                FROM SiteAwards AS saw
                LEFT JOIN GameData AS gd ON gd.ID = saw.AwardData
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
                WHERE saw.AwardType = 1 AND AwardData > 0 AND AwardDataExtra IS NOT NULL $friendCondAward
                AND saw.AwardDate BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)     
                ORDER BY AwardedAt DESC
                LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}
