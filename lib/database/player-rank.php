<?php

function SetUserUntrackedStatus($usernameIn, $isUntracked): void
{
    sanitize_sql_inputs($usernameIn, $isUntracked);

    $query = "UPDATE UserAccounts SET Untracked = $isUntracked, Updated=NOW() WHERE User = '$usernameIn'";
    s_mysql_query($query);
}

function getPlayerPoints($user): int
{
    sanitize_sql_inputs($user);

    $query = "SELECT ua.RAPoints
              FROM UserAccounts AS ua
              WHERE ua.User='$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $result = mysqli_fetch_assoc($dbResult);
        if ($result) {
            return (int) $result['RAPoints'];
        }
    }

    return 0;
}

function recalculatePlayerPoints($user): bool
{
    sanitize_sql_inputs($user);

    $query = "UPDATE UserAccounts SET RAPoints = (
                SELECT SUM(ach.Points) FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE aw.User = '$user' AND ach.Flags = 3
                ),
                TrueRAPoints = (
                SELECT SUM(ach.TrueRatio) FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE aw.User = '$user' AND ach.Flags = 3
                )
              WHERE User = '$user' ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return false;
    }

    return true;
}

function countRankedUsers(): int
{
    $query = "
        SELECT COUNT(*) AS count
        FROM UserAccounts
        WHERE RAPoints >= " . MIN_POINTS . "
          AND NOT Untracked
    ";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['count'];
}

function getTopUsersByScore($count, &$dataOut, $ofFriend = null): int
{
    sanitize_sql_inputs($count, $ofFriend);
    settype($count, 'integer');

    if ($count > 10) {
        $count = 10;
    }

    $subquery = "WHERE !ua.Untracked";
    if (isset($ofFriend)) {
        // $subquery = "WHERE ua.User IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$ofFriend' )
        // OR ua.User = '$ofFriend' ";
        // Only users whom I have added:
        $subquery = "WHERE !ua.Untracked AND ua.User IN ( SELECT f.Friend FROM Friends AS f WHERE f.User = '$ofFriend' AND f.Friendship = 1 )";
    }

    $query = "SELECT User, RAPoints, TrueRAPoints
              FROM UserAccounts AS ua
              $subquery
              ORDER BY RAPoints DESC 
              LIMIT 0, $count ";

    $dbResult = s_mysql_query($query);

    if (!$dbResult || mysqli_num_rows($dbResult) == 0) {
        // This is acceptable if the user doesn't have any friends!
        return 0;
    } else {
        $i = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            // $dataOut[$i][0] = $db_entry["ID"];
            $dataOut[$i][1] = $db_entry["User"];
            $dataOut[$i][2] = $db_entry["RAPoints"];
            $dataOut[$i][3] = $db_entry["TrueRAPoints"];
            $i++;
        }

        return $i;
    }
}

/**
 * Gets the points or retro points rank of the user.
 */
function getUserRank(string $user, int $type = 0): ?int
{
    sanitize_sql_inputs($user);

    // 0 for points rank, anything else for retro points rank
    if ($type == 0) {
        $joinCond = "RIGHT JOIN UserAccounts AS ua2 ON ua.RAPoints < ua2.RAPoints AND NOT ua2.Untracked";
    } else {
        $joinCond = "RIGHT JOIN UserAccounts AS ua2 ON ua.TrueRAPoints < ua2.TrueRAPoints AND NOT ua2.Untracked";
    }

    $query = "SELECT ( COUNT(*) + 1 ) AS UserRank, ua.Untracked
                FROM UserAccounts AS ua
                $joinCond
                WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        log_sql_fail();

        return null;
    }

    $data = mysqli_fetch_assoc($dbResult);
    if ($data['Untracked']) {
        return null;
    }

    return (int) $data['UserRank'];
}
