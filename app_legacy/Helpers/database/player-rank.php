<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use LegacyApp\Community\Enums\Rank;
use LegacyApp\Community\Enums\RankType;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Enums\UnlockMode;

function SetUserUntrackedStatus($usernameIn, $isUntracked): void
{
    sanitize_sql_inputs($usernameIn, $isUntracked);

    $query = "UPDATE UserAccounts SET Untracked = $isUntracked, Updated=NOW() WHERE User = '$usernameIn'";
    s_mysql_query($query);
}

function getPlayerPoints($user, &$dataOut): bool
{
    if (!isset($user) || mb_strlen($user) < 2) {
        return false;
    }

    sanitize_sql_inputs($user);

    $query = "SELECT ua.RAPoints, ua.RASoftcorePoints
              FROM UserAccounts AS ua
              WHERE ua.User='$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $dataOut = mysqli_fetch_assoc($dbResult);
        settype($dataOut['RAPoints'], 'integer');
        settype($dataOut['RASoftcorePoints'], 'integer');

        return true;
    }

    return false;
}

function recalculatePlayerPoints($user): bool
{
    sanitize_sql_inputs($user);

    $query = "UPDATE UserAccounts ua
                LEFT JOIN (
                    SELECT aw.User AS UserAwarded,
                    SUM(IF(aw.HardcoreMode = " . UnlockMode::Hardcore . ", ach.Points, 0)) AS HardcorePoints,
                    SUM(IF(aw.HardcoreMode = " . UnlockMode::Hardcore . ", ach.TrueRatio, 0)) AS TruePoints,
                    SUM(IF(aw.HardcoreMode = " . UnlockMode::Softcore . ", ach.Points, 0)) AS TotalPoints
                    FROM Awarded AS aw
                    LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                    WHERE aw.User = '$user' AND ach.Flags = " . AchievementType::OfficialCore . "
                ) hc ON ua.User = hc.UserAwarded
                SET RAPoints = COALESCE(hc.HardcorePoints, 0),
                    TrueRAPoints = COALESCE(hc.TruePoints, 0),
                    RASoftcorePoints = COALESCE(hc.TotalPoints - hc.HardcorePoints, 0)
                WHERE User = '$user'";

    $dbResult = s_mysql_query($query);
    if (!$dbResult) {
        return false;
    }

    return true;
}

function countRankedUsers(int $type = RankType::Hardcore): int
{
    $query = "SELECT COUNT(*) AS count FROM UserAccounts ";
    switch ($type) {
        case RankType::Hardcore:
            $query .= "WHERE RAPoints >= " . Rank::MIN_POINTS;
            break;

        case RankType::Softcore:
            $query .= "WHERE RASoftcorePoints >= " . Rank::MIN_POINTS;
            break;

        case RankType::TruePoints:
            $query .= "WHERE TrueRAPoints >= " . Rank::MIN_TRUE_POINTS;
            break;
    }

    $query .= " AND NOT Untracked";

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
        $friendSubquery = GetFriendsSubquery($ofFriend);
        $subquery = "WHERE !ua.Untracked AND ua.User IN ($friendSubquery)";
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
function getUserRank(string $user, int $type = RankType::Hardcore): ?int
{
    return Cache::remember('user:' . $user . ':rank:' . ($type === RankType::Hardcore ? 'hardcore' : 'softcore'), Carbon::now()->addMinutes(15), function () use ($user, $type) {
        sanitize_sql_inputs($user);

        $joinCond = match ($type) {
            default => "RIGHT JOIN UserAccounts AS ua2 ON ua.RAPoints < ua2.RAPoints AND NOT ua2.Untracked",
            RankType::Softcore => "RIGHT JOIN UserAccounts AS ua2 ON ua.RASoftcorePoints < ua2.RASoftcorePoints AND NOT ua2.Untracked",
            RankType::TruePoints => "RIGHT JOIN UserAccounts AS ua2 ON ua.TrueRAPoints < ua2.TrueRAPoints AND NOT ua2.Untracked",
        };

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
    });
}
