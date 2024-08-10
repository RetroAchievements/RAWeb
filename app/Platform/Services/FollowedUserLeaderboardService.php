<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\User;
use App\Platform\Enums\PlayerStatType;

class FollowedUserLeaderboardService
{
    private array $statTypeMap = [
        'PointsHardcoreDay' => PlayerStatType::PointsHardcoreDay,
        'PointsHardcoreWeek' => PlayerStatType::PointsHardcoreWeek,
        'PointsSoftcoreDay' => PlayerStatType::PointsSoftcoreDay,
        'PointsSoftcoreWeek' => PlayerStatType::PointsSoftcoreWeek,
        'PointsWeightedDay' => PlayerStatType::PointsWeightedDay,
        'PointsWeightedWeek' => PlayerStatType::PointsWeightedWeek,
    ];

    public function buildFollowedUserStats(User $user): array
    {
        $followedUsers = $user->followedUsers()
            ->where(function ($query) {
                $query->where('RAPoints', '>', 0)
                    ->orWhere('RASoftcorePoints', '>', 0);
            })
            ->with(['playerStats' => function ($query) {
                $types = [
                    PlayerStatType::PointsHardcoreDay,
                    PlayerStatType::PointsHardcoreWeek,
                    PlayerStatType::PointsSoftcoreDay,
                    PlayerStatType::PointsSoftcoreWeek,
                    PlayerStatType::PointsWeightedDay,
                    PlayerStatType::PointsWeightedWeek,
                ];

                $query->select('user_id', 'type', 'value')
                    ->whereIn('type', $types)
                    ->where('value', '>', 0)
                    ->orderByDesc('value')
                    ->limit(700);
            }])
            ->get();

        $statsDaily = [];
        $statsWeekly = [];
        $statsAllTime = [];

        foreach ($followedUsers as $followedUser) {
            $daily = $this->aggregateStats($followedUser->playerStats, 'Day');
            $weekly = $this->aggregateStats($followedUser->playerStats, 'Week');

            // Don't include users who have 0 points for the day or week.
            if (array_sum($daily) > 0) {
                $statsDaily[] = ['user' => $followedUser->display_name] + $daily;
            }
            if (array_sum($weekly) > 0) {
                $statsWeekly[] = ['user' => $followedUser->display_name] + $weekly;
            }

            if ($followedUser->points > 0) {
                $statsAllTime[] = [
                    'user' => $followedUser->display_name,
                    'points_hardcore' => $followedUser->points,
                    'points_softcore' => $followedUser->points_softcore,
                    'points_weighted' => $followedUser->points_weighted,
                ];
            }
        }

        $userDaily = $this->aggregateStats($user->playerStats, 'Day');
        $userWeekly = $this->aggregateStats($user->playerStats, 'Week');
        if (array_sum($userDaily) > 0) {
            $statsDaily[] = ['user' => $user->display_name] + $userDaily;
        }
        if (array_sum($userWeekly) > 0) {
            $statsWeekly[] = ['user' => $user->display_name] + $userWeekly;
        }

        // Include the current user in the all-time list.
        $statsAllTime[] = [
            'user' => $user->display_name,
            'points_hardcore' => $user->points,
            'points_softcore' => $user->points_softcore,
            'points_weighted' => $user->points_weighted,
        ];

        // Sort and limit the results
        usort($statsDaily, function ($a, $b) { return $b['points_hardcore'] <=> $a['points_hardcore']; });
        usort($statsWeekly, function ($a, $b) { return $b['points_hardcore'] <=> $a['points_hardcore']; });
        usort($statsAllTime, function ($a, $b) { return $b['points_hardcore'] <=> $a['points_hardcore']; });

        $userDailyRanking = $this->findUserRanking($user, $statsDaily);
        $userWeeklyRanking = $this->findUserRanking($user, $statsWeekly);
        $userAllTimeRanking = $this->findUserRanking($user, $statsAllTime);

        $statsDaily = array_slice($statsDaily, 0, 10);
        $statsWeekly = array_slice($statsWeekly, 0, 10);
        $statsAllTime = array_slice($statsAllTime, 0, 10);

        // Include user ranking if they are outside the top 10.
        if ($userDailyRanking) {
            $statsDaily['userRanking'] = $userDailyRanking;
        }
        if ($userWeeklyRanking) {
            $statsWeekly['userRanking'] = $userWeeklyRanking;
        }
        if ($userAllTimeRanking) {
            $statsAllTime['userRanking'] = $userAllTimeRanking;
        }

        return compact('statsDaily', 'statsWeekly', 'statsAllTime');
    }

    private function aggregateStats(mixed $playerStats, string $timeframe): array
    {
        $hardcore = $this->getStatTypeEnumValue("PointsHardcore", $timeframe);
        $softcore = $this->getStatTypeEnumValue("PointsSoftcore", $timeframe);
        $weighted = $this->getStatTypeEnumValue("PointsWeighted", $timeframe);

        return [
            'points_hardcore' => $hardcore ? $playerStats->where('type', $hardcore)->value('value') ?? 0 : 0,
            'points_softcore' => $softcore ? $playerStats->where('type', $softcore)->value('value') ?? 0 : 0,
            'points_weighted' => $weighted ? $playerStats->where('type', $weighted)->value('value') ?? 0 : 0,
        ];
    }

    private function getStatTypeEnumValue(string $statType, string $timeframe): string
    {
        $key = "{$statType}{$timeframe}";

        return $this->statTypeMap[$key] ?? '';
    }

    // If the user is outside the top 10 of their followed users all time rankings,
    // find their ranking and return an element dedicated just to them.
    private function findUserRanking(User $user, array $stats): ?array
    {
        // This naively disregards ties.
        $userIndex = null;
        foreach ($stats as $index => $rank) {
            if ($rank['user'] === $user->display_name) {
                $userIndex = $index;
                break;
            }
        }

        // If we fall into this block, the user is in the top 10 or wasn't found.
        if ($userIndex === null || $userIndex < 10) {
            return null;
        }

        return array_merge($stats[$userIndex], ['rank' => $userIndex + 1]);
    }
}
