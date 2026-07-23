<?php

use App\Community\Enums\RankType;
use App\Models\PlayerGlobalRanking;
use App\Models\PlayerGlobalRankingTotal;
use App\Models\User;
use App\Platform\Enums\GlobalRankingWindow;
use App\Platform\Events\PlayerRankedStatusChanged;

function SetUserUntrackedStatus(User $user, bool $isUntracked): void
{
    $user->unranked_at = $isUntracked ? now() : null;
    $user->save();

    PlayerRankedStatusChanged::dispatch($user);
}

function countRankedUsers(RankType $type = RankType::Hardcore): int
{
    return PlayerGlobalRankingTotal::forRankType($type);
}

function getTopUsersByScore(int $count): array
{
    $topUsers = User::select(['ulid', 'display_name', 'username', 'points_hardcore', 'points_weighted'])
        ->whereNull('unranked_at')
        ->orderBy('points_hardcore', 'desc')
        ->take(min($count, 10))
        ->get()
        ->map(fn ($user) => [
            1 => $user->display_name ?? $user->username,
            2 => $user->points_hardcore,
            3 => $user->points_weighted,
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
function getUserRank(string $username, RankType $type = RankType::Hardcore): ?int
{
    $user = User::whereName($username)->first(['id', 'unranked_at']);
    if ($user === null || $user->unranked_at !== null) {
        return null;
    }

    $rank = PlayerGlobalRanking::query()
        ->where('user_id', $user->id)
        ->where('window', GlobalRankingWindow::AllTime)
        ->where('mode', $type->mode())
        ->value($type->rankColumn());

    return $rank !== null ? (int) $rank : null;
}
