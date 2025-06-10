<?php

use App\Community\Enums\Rank;
use App\Community\Enums\RankType;
use App\Models\UnrankedUser;
use App\Models\User;
use App\Platform\Events\PlayerRankedStatusChanged;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

function SetUserUntrackedStatus(User $user, bool $isUntracked): void
{
    $user->Untracked = $isUntracked;
    $user->unranked_at = $isUntracked ? now() : null;
    $user->save();

    if ($isUntracked) {
        UnrankedUser::firstOrCreate(['user_id' => $user->id]);
    } else {
        UnrankedUser::where('user_id', $user->id)->delete();
    }

    PlayerRankedStatusChanged::dispatch($user, !$isUntracked);
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
    $topUsers = User::select(['ulid', 'display_name', 'User', 'RAPoints', 'TrueRAPoints'])
        ->where('Untracked', false)
        ->orderBy('RAPoints', 'desc')
        ->take(min($count, 10))
        ->get()
        ->map(fn ($user) => [
            1 => $user->display_name ?? $user->User,
            2 => $user->RAPoints,
            3 => $user->TrueRAPoints,
            4 => $user->ulid,
        ])
        ->toArray();

    // First sort by RAPoints (key 2), then by TrueRAPoints (key 3) if RAPoints are equal.
    uasort($topUsers, function ($a, $b) {
        return ($a[2] === $b[2]) ? ($b[3] <=> $a[3]) : ($b[2] <=> $a[2]);
    });

    return $topUsers;
}

/**
 * Gets the points or retro points rank of the user.
 */
function getUserRank(string $username, int $type = RankType::Hardcore): ?int
{
    $key = CacheKey::buildUserRankCacheKey($username, $type);

    return Cache::remember($key, Carbon::now()->addMinutes(15), function () use ($username, $type) {
        $user = User::whereName($username)->first();
        if (!$user || $user->Untracked) {
            return null;
        }

        $field = match ($type) {
            RankType::Softcore => 'RASoftcorePoints',
            RankType::TruePoints => 'TrueRAPoints',
            default => 'RAPoints',
        };

        $points = $user->$field;
        $minPoints = $type === RankType::TruePoints ? Rank::MIN_TRUE_POINTS : Rank::MIN_POINTS;

        if ($points < $minPoints) {
            return null;
        }

        return User::where($field, '>', $points)
            ->where('Untracked', false)
            ->count() + 1;
    });
}
