<?php

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Platform\Enums\AchievementType;
use App\Platform\Enums\UnlockMode;
use App\Site\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

function SetUserUntrackedStatus(string $usernameIn, int $isUntracked): void
{
    $query = "UPDATE UserAccounts SET Untracked = $isUntracked, Updated=NOW() WHERE User = '$usernameIn'";
    s_mysql_query($query);
}

function getPlayerPoints(?string $user, ?array &$dataOut): bool
{
    if (empty($user) || !isValidUsername($user)) {
        return false;
    }

    $query = "SELECT ua.RAPoints, ua.RASoftcorePoints
              FROM UserAccounts AS ua
              WHERE ua.User=:username";

    $dataOut = legacyDbFetch($query, ['username' => $user]);
    if ($dataOut) {
        $dataOut['RAPoints'] = (int) $dataOut['RAPoints'];
        $dataOut['RASoftcorePoints'] = (int) $dataOut['RASoftcorePoints'];

        return true;
    }

    return false;
}

function recalculatePlayerPoints(string $user): bool
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

    return (bool) $dbResult;
}

function countRankedUsers(int $type = RankType::Hardcore): int
{
    return Cache::remember("rankedUserCount:$type",
        60, // expire once a minute
        function () use ($type) {
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

            return (int) legacyDbFetch($query)['count'];
        });
}

function getTopUsersByScore(int $count): array
{
    if ($count > 10) {
        $count = 10;
    }

    $query = "SELECT User, RAPoints, TrueRAPoints
              FROM UserAccounts AS ua
              WHERE NOT ua.Untracked
              ORDER BY RAPoints DESC, TrueRAPoints DESC
              LIMIT 0, $count ";

    return legacyDbFetchAll($query)->map(fn ($row) => [
        1 => $row['User'],
        2 => $row['RAPoints'],
        3 => $row['TrueRAPoints'],
    ])->toArray();
}

/**
 * Gets the points or retro points rank of the user.
 */
function getUserRank(string $username, int $type = RankType::Hardcore): ?int
{
    $key = "user:$username:rank:";
    $key .= match ($type) {
        default => 'hardcore',
        RankType::Softcore => 'softcore',
        RankType::TruePoints => 'truepoints',
    };

    return Cache::remember($key, Carbon::now()->addMinutes(15), function () use ($username, $type) {
        $user = User::firstWhere('User', $username);
        if (!$user || $user->Untracked) {
            return null;
        }

        switch ($type) {
            default: // hardcore
                $points = $user->RAPoints;
                if ($points < Rank::MIN_POINTS) {
                    return null;
                }

                $field = 'RAPoints';
                break;

            case RankType::Softcore:
                $points = $user->RASoftcorePoints;
                if ($points < Rank::MIN_POINTS) {
                    return null;
                }

                $field = 'RASoftcorePoints';
                break;

            case RankType::TruePoints:
                $points = $user->TrueRAPoints;
                if ($points < Rank::MIN_TRUE_POINTS) {
                    return null;
                }

                $field = 'TrueRAPoints';
                break;
        }

        $query = "SELECT ( COUNT(*) + 1 ) AS UserRank
                  FROM UserAccounts AS ua
                  WHERE ua.$field > $points AND NOT ua.Untracked";

        return (int) legacyDbFetch($query)['UserRank'];
    });
}
