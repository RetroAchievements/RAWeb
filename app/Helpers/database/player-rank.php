<?php

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Site\Models\User;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

function SetUserUntrackedStatus(string $usernameIn, int $isUntracked): void
{
    legacyDbStatement("UPDATE UserAccounts SET Untracked = $isUntracked, Updated=NOW() WHERE User = '$usernameIn'");

    PlayerRankedStatusChanged::dispatch(User::firstWhere('User', $usernameIn), (bool) $isUntracked);

    // TODO update games that are affected by this user's library
}

/**
 * @deprecated take from authenticated user directly
 */
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

function countRankedUsers(int $type = RankType::Hardcore): int
{
    return Cache::remember("rankedUserCount:$type",
        Carbon::now()->addMinute(),
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
    $key = CacheKey::buildUserRankCacheKey($username, $type);

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
