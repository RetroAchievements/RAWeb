<?php

use App\Community\Enums\AwardType;
use App\Community\Enums\Rank;
use App\Models\User;
use App\Platform\Enums\UnlockMode;

/**
 * Renders the friends and global ranking.
 */
function RenderPointsRankingComponent(User $user, bool $friendsOnly, int $numToFetch = 10): void
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
        $lbTypesCount = count($lbTypes);
        for ($i = 0; $i < $lbTypesCount; $i++) {
            if ($i == 0) {
                echo "<button type='button' class='" . $tabClass . " active' onclick='handleLeaderboardTabClick(event, \"" . $lbTypes[$i] . $id . "\", \"" . $tabClass . "\")'>" . $lbNames[$i] . "</button>";
            } else {
                echo "<button type='button' class='" . $tabClass . "' onclick='handleLeaderboardTabClick(event, \"" . $lbTypes[$i] . $id . "\", \"" . $tabClass . "\")'>" . $lbNames[$i] . "</button>";
            }
        }
        echo "</div>";

        // Populate the tabs contents with the leaderboard table
        $lbTypesCount = count($lbTypes);
        for ($j = 0; $j < $lbTypesCount; $j++) {
            if ($j == 0) {
                echo "<div id='" . $lbTypes[$j] . $id . "' class='tabcontent" . $tabClass . "' style=\"display:block\">";
            } else {
                echo "<div id='" . $lbTypes[$j] . $id . "' class='tabcontent" . $tabClass . "'>";
            }

            if ($friendsOnly) {
                $data = getGlobalRankingData($j, 5, $currentDate, null, $user->User, 0, 0, $friendCount + 1, 1);
            } else {
                $data = getGlobalRankingData($j, 5, $currentDate, null, null, 0, 0, $numToFetch, 1);
            }

            $rank = 1;
            $userRank = 0;
            $userListed = false;
            $keepAddingRows = true;
            $dateUnix = strtotime($currentDate);
            echo "<table class='table-highlight mb-3'><tbody>";

            // Create table headers
            echo "<tr class='do-not-highlight'><th>Rank</th><th>User</th><th>Points</th></tr>";
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
                    if ($user !== null && $user->User == $dataPoint['User']) {
                        echo "<tr style='outline: thin solid'>";
                        $userListed = true;
                    } else {
                        echo "<tr>";
                    }
                    echo "<td class='rank'>" . $rank . "</td>";
                    echo "<td>";
                    echo userAvatar($dataPoint['User']);
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
                    if ($user !== null && $user->User == $dataPoint['User']) {
                        $userRank = $rank;
                        break;
                    }
                }
                $rank++;
            }

            // Display the current user at the bottom of the list if they are not already included
            if ($user !== null && !$userListed) {
                $userData = getGlobalRankingData($j, 5, $currentDate, $user->User, null, 0, 0, 1, 1);
                if (!empty($userData)) {
                    echo "<tr><td colspan='3'></td></tr>";
                    echo "<tr style='outline: thin solid'>";

                    if ($j == 2 && !$friendsOnly) {
                        echo "<td class='rank'>" . getUserRank($user->User, 0) . "</td>";
                    } elseif ($friendsOnly) {
                        echo "<td>" . $userRank . "</td>";
                    } else {
                        echo "<td></td>";
                    }
                    echo "<td>";
                    echo userAvatar($user->User);
                    echo "</td>";
                    if ($j == 0) {
                        echo "<td><a href='/historyexamine.php?d=$dateUnix&u=" . $user->User . "'>" . $user->points . "</a>";
                    } else {
                        echo "<td>" . $user->points;
                    }
                    echo " <span class='TrueRatio'>(" . $user->points_weighted . ")</span></td>";
                }
            }
            echo "</tbody></table>";

            // Display the more buttons that link to the global ranking page for the specific leaderboard type
            if (!$friendsOnly) {
                echo "<div class='text-right'><a class='btn btn-link' href='/globalRanking.php?t=" . $j . "'>more...</a></div>";
            } else {
                echo "<div class='text-right'><a class='btn btn-link' href='/globalRanking.php?t=" . $j . "&f=1'>more...</a></div>";
            }
            echo "</div>";
        }
    }
    echo "</div>";
}

/**
 * Creates the High scores tables on game pages
 */
function RenderTopAchieversComponent(
    ?string $user,
    array $gameTopAchievers,
    array $gameLatestMasters
): void {
    echo "<div id='leaderboard' class='component' >";

    $numLatestMasters = count($gameLatestMasters);
    $numTopAchievers = count($gameTopAchievers);
    $masteryThreshold = 10; // Number of masters needed for the "Latest Masters" tab to be selected by default

    echo "<h2 class='text-h3'>High Scores</h2>";
    echo "<div class='tab'>";
    echo "<button type='button' class='scores" . ($numLatestMasters >= $masteryThreshold ? " active" : "") . "' onclick='handleLeaderboardTabClick(event, \"latestmasters\", \"scores\")'>Latest Masters</button>";
    echo "<button type='button' class='scores" . ($numLatestMasters >= $masteryThreshold ? "" : " active") . "' onclick='handleLeaderboardTabClick(event, \"highscores\", \"scores\")'>High Scores</button>";
    echo "</div>";

    // Latest Masters Tab
    echo "<div id='latestmasters' class='tabcontentscores' style=\"display: " . ($numLatestMasters >= $masteryThreshold ? "block" : "none") . "\">";
    echo "<table class='table-highlight'><tbody>";
    echo "<tr class='do-not-highlight'><th>#</th><th>User</th><th>Mastered</th></tr>";

    for ($i = 0; $i < $numLatestMasters; $i++) {
        if (!isset($gameLatestMasters[$i])) {
            continue;
        }

        $nextRank = $gameLatestMasters[$i]['Rank'];
        $nextUser = $gameLatestMasters[$i]['User'];
        $date = date_create($gameLatestMasters[$i]['LastAward']);
        $nextLastAward = date_format($date, "Y-m-d H:i");

        // Outline user if they are in the list
        if ($user !== null && $user == $nextUser) {
            echo "<tr style='outline: thin solid'>";
        } else {
            echo "<tr>";
        }

        echo "<td>";
        echo $nextRank;
        echo "</td>";

        echo "<td>";
        echo userAvatar($nextUser, iconSize: 16);
        echo "</td>";

        echo "<td>";
        echo $nextLastAward;
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";

    // High Scores Tab
    echo "<div id='highscores' class='tabcontentscores' style=\"display: " . ($numLatestMasters >= $masteryThreshold ? "none" : "block") . "\">";
    echo "<table class='table-highlight'><tbody>";
    echo "<tr class='do-not-highlight'><th>#</th><th>User</th><th>Total points</th></tr>";

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

        echo "<td>";
        echo $i + 1;
        echo "</td>";

        echo "<td>";
        echo userAvatar($nextUser, iconSize: 16);
        echo "</td>";

        echo "<td>";
        echo "<span class='cursor-help' title='Latest awarded at $nextLastAward'>$nextPoints</span>";
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
 * @param string|null $friendsOf User to get friends data for
 * @param int $untracked Option to include or exclude untracked users
 *            0 - Tracked users only
 *            1 - Untracked users only
 *            2 - Tracked and untracked user
 * @param int $offset starting point to return rows
 * @param int $count number of rows to return
 * @param int $info amount of information to pull from the database
 *            0 - All ranking stats
 *            1 - Just Hardcore Points and Retro Points. Used for the sidebar rankings.
 */
function getGlobalRankingData(
    int $lbType,
    int $sort,
    string $date,
    ?string $user,
    ?string $friendsOf = null,
    int $untracked = 0,
    int $offset = 0,
    int $count = 50,
    int $info = 0
): array {
    $pointRequirement = "";

    $unlockMode = UnlockMode::Hardcore;

    $typeCond = match ($lbType) {
        // Daily
        0 => "BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)",
        // Weekly
        1 => "BETWEEN TIMESTAMP(SUBDATE('$date', DAYOFWEEK('$date') - 1)) AND DATE_ADD(DATE_ADD(SUBDATE('$date', DAYOFWEEK('$date') - 1), INTERVAL 6 DAY), INTERVAL 24 * 60 * 60 - 1 SECOND)",
        // Daily by default
        default => "BETWEEN TIMESTAMP('$date') AND DATE_ADD('$date', INTERVAL 24 * 60 * 60 - 1 SECOND)",
    };

    // Determine ascending or descending order
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
        $singleUserAchievementCond = "AND ua.User LIKE '$user'";
        $singleUserAwardCond = "AND ua.User LIKE '$user'";
        $singleUserCond = "AND ua.User LIKE '$user'";
    }

    // Determine the friends condition
    $friendCondAchievement = "";
    $friendCondAward = "";
    $friendCondAllTime = "";
    if ($friendsOf !== null) {
        $friendsSubquery = GetFriendsSubquery($friendsOf);

        $friendCondAchievement = "AND ua.User IN ($friendsSubquery)";
        $friendCondAward = "AND ua.User IN ($friendsSubquery)";
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

    $masteryCond = "AND AwardType = " . AwardType::Mastery;

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
                $selectQuery = "SELECT ua.ID, ua.User,
                        COALESCE(ua.achievements_unlocked_hardcore, 0) AS AchievementCount,
                        COALESCE(ua.RAPoints, 0) AS Points,
                        COALESCE(ua.TrueRAPoints, 0) AS RetroPoints,
                        COALESCE(ROUND(ua.TrueRAPoints/ua.RAPoints, 2), 0) AS RetroRatio ";
            } else {
                $selectQuery = "SELECT ua.ID, ua.User,
                        COALESCE(ua.achievements_unlocked - ua.achievements_unlocked_hardcore, 0) AS AchievementCount,
                        COALESCE(ua.RASoftcorePoints, 0) AS Points,
                        0 AS RetroPoints,
                        0 AS RetroRatio ";
            }
        } else {
            if ($unlockMode == UnlockMode::Hardcore) {
                $selectQuery = "SELECT ua.ID, ua.User,
                        COALESCE(ua.RAPoints, 0) AS Points,
                        COALESCE(ua.TrueRAPoints, 0) AS RetroPoints ";
            } else {
                $selectQuery = "SELECT ua.ID, ua.User,
                        COALESCE(ua.RASoftcorePoints, 0) AS Points,
                        0 AS RetroPoints ";
            }
        }

        // TODO slow query (60)
        $query = "$selectQuery
                    FROM UserAccounts AS ua
                    WHERE TRUE $untrackedCond $singleUserCond $pointRequirement $friendCondAllTime
                    $orderCond, ua.User
                    LIMIT $offset, $count";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $userIds = [];
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $retVal[] = $db_entry;
                $userIds[] = $db_entry['ID'];
            }

            // Get site award info for each user.
            $usersCount = count($userIds);
            for ($i = 0; $i < $usersCount; $i++) {
                $query2 = "SELECT $totalAwards AS TotalAwards FROM SiteAwards WHERE user_id = '" . $userIds[$i] . "' " . $masteryCond;

                $dbResult2 = s_mysql_query($query2);
                if ($dbResult2 !== false) {
                    $db_entry2 = mysqli_fetch_assoc($dbResult2);
                    $retVal[$i]['TotalAwards'] = $db_entry2['TotalAwards'];
                }
            }
        }

        return $retVal;
    }

    if ($unlockMode == UnlockMode::Hardcore) {
        $whereDateAchievement = 'AND aw.unlocked_hardcore_at';
    } else {
        $whereDateAchievement = 'AND aw.unlocked_at';
    }

    // Just Hardcore Points and Retro Points. Used for the sidebar rankings
    if ($info == 1) {
        return legacyDbFetchAll("
            SELECT ua.User AS User,
            SUM(ach.Points) AS Points,
            SUM(ach.TrueRatio) AS RetroPoints
            FROM player_achievements AS aw
            INNER JOIN Achievements AS ach ON ach.ID = aw.achievement_id
            INNER JOIN UserAccounts AS ua ON ua.ID = aw.user_id
            WHERE TRUE $whereDateAchievement $typeCond
            $friendCondAchievement
            $singleUserAchievementCond
            $untrackedCond
            GROUP BY ua.User
            $orderCond
            LIMIT $offset, $count
        ")->toArray();
    }

    // All ranking stats

    if ($unlockMode == UnlockMode::Hardcore) {
        $achPoints = "CASE WHEN aw.unlocked_hardcore_at IS NOT NULL THEN ach.Points ELSE 0 END";
        $achCount = "CASE WHEN aw.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END";
        $achTruePoints = "CASE WHEN aw.unlocked_hardcore_at IS NOT NULL THEN ach.TrueRatio ELSE 0 END";
    } else {
        $achPoints = "CASE WHEN aw.unlocked_at IS NOT NULL THEN ach.Points ELSE -ach.Points END";
        $achCount = "CASE WHEN aw.unlocked_at IS NOT NULL THEN 1 ELSE -1 END";
        $achTruePoints = 0;
    }

    return legacyDbFetchAll("
        SELECT User,
            COALESCE(MAX(AchievementCount), 0) AS AchievementCount,
            COALESCE(MAX(Points), 0) AS Points,
            COALESCE(MAX(RetroPoints), 0) AS RetroPoints,
            ROUND(RetroPoints/Points, 2) AS RetroRatio,
            COALESCE(MAX(TotalAwards), 0) AS TotalAwards
        FROM
        (
            (
                SELECT ua.User AS User,
                    ua.ID as user_id,
                    SUM($achCount) AS AchievementCount,
                    SUM($achPoints) as Points,
                    SUM($achTruePoints) AS RetroPoints,
                    NULL AS TotalAwards
                FROM player_achievements AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.achievement_id
                LEFT JOIN UserAccounts AS ua ON ua.ID = aw.user_id
                WHERE TRUE $whereDateAchievement $typeCond
                    $friendCondAchievement
                    $singleUserAchievementCond
                    $untrackedCond
                GROUP BY ua.ID
            )
            UNION
            (
                SELECT ua.User AS User,
                    ua.ID AS user_id,
                    NULL AS AchievementCount,
                    NULL AS Points,
                    NULL AS RetroPoints,
                    $totalAwards AS TotalAwards
                FROM SiteAwards AS sa
                LEFT JOIN UserAccounts AS ua ON ua.ID = sa.user_id
                WHERE TRUE AND sa.AwardDate $typeCond
                    $friendCondAward
                    $singleUserAwardCond
                    $masteryCond
                    $untrackedCond
                GROUP BY ua.ID
            )
        ) AS Query
        GROUP BY user_id
        HAVING Points > 0 AND AchievementCount > 0
        $orderCond
        LIMIT $offset, $count
    ")->toArray();
}
