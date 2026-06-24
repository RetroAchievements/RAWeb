<?php

use App\Models\PlayerAchievement;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

function getUserBestDaysList(User $user, int $offset, int $limit, int $sortBy): array
{
    if ($sortBy < 1 || $sortBy > 13) {
        $sortBy = 1;
    }

    [$orderColumn, $orderDirection] = match ($sortBy) {
        1 => ['Date', 'desc'],
        2 => ['NumAwarded', 'desc'],
        3 => ['TotalPointsEarned', 'desc'],
        11 => ['Date', 'asc'],
        12 => ['NumAwarded', 'asc'],
        13 => ['TotalPointsEarned', 'asc'],
        default => [null, null],
    };

    $query = PlayerAchievement::query()
        ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
        ->join('games', 'games.id', '=', 'achievements.game_id')
        ->where('player_achievements.user_id', $user->id)
        ->where('achievements.is_promoted', 1)
        ->where('games.system_id', '!=', System::Events)
        ->selectRaw('DATE(player_achievements.unlocked_effective_at) AS Date')
        ->selectRaw('COUNT(*) AS NumAwarded')
        ->selectRaw('SUM(achievements.points) AS TotalPointsEarned')
        ->groupBy('Date');

    if ($orderColumn !== null) {
        $query->orderBy($orderColumn, $orderDirection);
    }

    return $query
        ->offset($offset)
        ->limit($limit)
        ->get()
        ->map(fn ($row) => $row->getAttributes())
        ->toArray();
}

function getAchievementsEarnedBetween(string $dateStart, string $dateEnd, User $user): array
{
    $bindings = [
        'dateStart' => $dateStart,
        'dateEnd' => $dateEnd,
        'userid' => $user->id,
        'isPromoted' => 1,
    ];

    $cumulativeScore = 0;

    return PlayerAchievement::query()
        ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
        ->join('games', 'games.id', '=', 'achievements.game_id')
        ->join('systems', 'systems.id', '=', 'games.system_id')
        ->join('users', 'users.id', '=', 'achievements.user_id')
        ->where('player_achievements.user_id', $bindings['userid'])
        ->where('achievements.is_promoted', $bindings['isPromoted'])
        ->whereBetween('player_achievements.unlocked_effective_at', [$bindings['dateStart'], $bindings['dateEnd']])
        ->selectRaw(
            'player_achievements.unlocked_effective_at AS Date, '
            . 'CASE WHEN player_achievements.unlocked_hardcore_at IS NOT NULL THEN 1 ELSE 0 END AS HardcoreMode, '
            . 'achievements.id AS AchievementID, '
            . 'achievements.title AS Title, '
            . 'achievements.description AS Description, '
            . 'achievements.image_name AS BadgeName, '
            . 'achievements.points AS Points, '
            . 'achievements.points_weighted AS TrueRatio, '
            . 'achievements.type AS Type, '
            . 'COALESCE(users.display_name, users.username) AS Author, '
            . 'users.ulid AS AuthorULID, '
            . 'games.title AS GameTitle, '
            . 'games.image_icon_asset_path AS GameIcon, '
            . 'achievements.game_id AS GameID, '
            . 'systems.name AS ConsoleName'
        )
        ->orderBy('Date')
        ->orderByDesc('HardcoreMode')
        ->limit(500)
        ->get()
        ->map(fn ($row) => $row->getAttributes())
        ->map(function ($entry) use (&$cumulativeScore) {
            $cumulativeScore += (int) $entry['Points'];
            $entry['CumulScore'] = $cumulativeScore;

            settype($entry['AchievementID'], 'integer');
            settype($entry['Points'], 'integer');
            settype($entry['HardcoreMode'], 'integer');
            settype($entry['GameID'], 'integer');

            return $entry;
        })
        ->toArray();
}

function getAchievementsEarnedOnDay(int $unixTimestamp, User $user): array
{
    $dateStrStart = date("Y-m-d 00:00:00", $unixTimestamp);
    $dateStrEnd = date("Y-m-d 23:59:59", $unixTimestamp);

    return getAchievementsEarnedBetween($dateStrStart, $dateStrEnd, $user);
}

function getAwardedList(
    User $user,
    bool $excludeEvents = true,
    ?int $offset = null,
    ?int $limit = null,
    ?string $dateFrom = null,
    ?string $dateTo = null,
): array {
    $rows = PlayerAchievement::query()
        ->join('achievements', 'achievements.id', '=', 'player_achievements.achievement_id')
        ->join('games', 'games.id', '=', 'achievements.game_id')
        ->where('player_achievements.user_id', $user->id)
        ->where('achievements.is_promoted', 1)
        ->when(
            $excludeEvents,
            fn (Builder $q) => $q->where('games.system_id', '!=', System::Events),
        )
        ->when(
            isset($dateFrom, $dateTo),
            fn (Builder $q) => $q->whereBetween(
                'player_achievements.unlocked_effective_at',
                [$dateFrom, $dateTo],
            ),
        )
        ->selectRaw('DATE(player_achievements.unlocked_effective_at) AS Date')
        ->selectRaw(
            'SUM(CASE WHEN player_achievements.unlocked_hardcore_at IS NOT NULL '
            . 'THEN achievements.points ELSE 0 END) AS HardcorePoints'
        )
        ->selectRaw('SUM(achievements.points) AS SoftcorePoints')
        ->groupBy('Date')
        ->orderBy('Date')
        ->when(
            isset($offset, $limit),
            fn (Builder $q) => $q->offset($offset)->limit($limit),
        )
        ->get();

    $cumulHardcoreScore = 0;
    $cumulCasualScore = 0;

    return $rows
        ->map(function ($row) use (&$cumulHardcoreScore, &$cumulCasualScore) {
            $hardcorePoints = (int) $row->HardcorePoints;
            $allPoints = (int) $row->SoftcorePoints;

            $cumulHardcoreScore += $hardcorePoints;
            $cumulCasualScore += $allPoints - $hardcorePoints;

            return [
                'Date' => $row->Date,
                'HardcorePoints' => $row->HardcorePoints,
                'SoftcorePoints' => $row->SoftcorePoints,
                'CumulHardcoreScore' => $cumulHardcoreScore,
                'CumulCasualScore' => $cumulCasualScore,
            ];
        })
        ->values()
        ->all();
}
