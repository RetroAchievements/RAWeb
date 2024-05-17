<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CompareUnlocksPageService
{
    public function buildViewData(Request $request, User $user, Game $game): array
    {
        $activeUser = $request->user();

        $validatedData = $request->validate([
            'sort' => 'sometimes|string|in:selfUnlocks,otherUnlocks,display,title',
        ]);
        $sortOrder = $validatedData['sort'] ?? 'display';

        $achievements = [];
        foreach ($game->achievements()->published()->get() as $achievement) {
            $achievements[$achievement->ID] = $achievement->toArray();
        }

        $this->mergeUserUnlocks($achievements, $activeUser, 'user');
        $this->mergeUserUnlocks($achievements, $user, 'otherUser');

        $this->sortList($achievements, $sortOrder);

        return array_merge(
            [
                'achievements' => $achievements,
                'game' => $game,
                'numAchievements' => count($achievements),
                'otherUser' => $user,
                'sortOrder' => $sortOrder,
                'user' => $activeUser,
            ],
            $this->buildUsersUnlockCounts($achievements),
        );
    }

    private function buildUsersUnlockCounts(array $achievements): array
    {
        $userUnlockCount = 0;
        $otherUserUnlockCount = 0;
        $userUnlockHardcoreCount = 0;
        $otherUserUnlockHardcoreCount = 0;

        foreach ($achievements as $achievement) {
            if (array_key_exists('userTimestamp', $achievement)) {
                $userUnlockCount++;
                if ($achievement['userHardcore'] ?? false) {
                    $userUnlockHardcoreCount++;
                }
            }

            if (array_key_exists('otherUserTimestamp', $achievement)) {
                $otherUserUnlockCount++;
                if ($achievement['otherUserHardcore'] ?? false) {
                    $otherUserUnlockHardcoreCount++;
                }
            }
        }

        return [
            'userUnlockCount' => $userUnlockCount,
            'otherUserUnlockCount' => $otherUserUnlockCount,
            'userUnlockHardcoreCount' => $userUnlockHardcoreCount,
            'otherUserUnlockHardcoreCount' => $otherUserUnlockHardcoreCount,
        ];
    }

    private function mergeUserUnlocks(array &$achievements, User $user, string $prefix): void
    {
        $userUnlocks = $user->playerAchievements()
            ->whereIn('achievement_id', array_keys($achievements))
            ->select(['achievement_id', 'unlocked_at', 'unlocked_hardcore_at']);

        foreach ($userUnlocks->get() as $unlock) {
            if ($unlock->unlocked_hardcore_at) {
                $achievements[$unlock->achievement_id][$prefix . 'TimestampRaw'] = $unlock->unlocked_hardcore_at;
                $achievements[$unlock->achievement_id][$prefix . 'Timestamp'] = Carbon::parse($unlock->unlocked_hardcore_at)->format('d M Y, g:ia');
                $achievements[$unlock->achievement_id][$prefix . 'Hardcore'] = true;
            } elseif ($unlock->unlocked_at) {
                $achievements[$unlock->achievement_id][$prefix . 'TimestampRaw'] = $unlock->unlocked_at;
                $achievements[$unlock->achievement_id][$prefix . 'Timestamp'] = Carbon::parse($unlock->unlocked_at)->format('d M Y, g:ia');
                $achievements[$unlock->achievement_id][$prefix . 'Hardcore'] = false;
            }
        }
    }

    private function sortList(array &$achievements, string $sortOrder): void
    {
        $sortFunction = match ($sortOrder) {
            default => function ($a, $b) {
                return $this->sortByUnlockTimestamps($a, $b, 'userTimestampRaw');
            },
            'otherUnlocks' => function ($a, $b) {
                return $this->sortByUnlockTimestamps($a, $b, 'otherUserTimestampRaw');
            },
            'display' => function ($a, $b) {
                return $a['DisplayOrder'] <=> $b['DisplayOrder'];
            },
            'title' => function ($a, $b) {
                return $a['Title'] <=> $b['Title'];
            },
        };

        usort($achievements, $sortFunction);
    }

    private function sortByUnlockTimestamps(array $a, array $b, string $field): int
    {
        // '~' is guaranteed to be lexigraphically after any date time (regardless of the
        // format) because it follows all alphanumeric characters in the ASCII table.
        $aTimestamp = $a[$field] ?? '~';
        $bTimestamp = $b[$field] ?? '~';
        if ($aTimestamp != $bTimestamp) {
            return $aTimestamp <=> $bTimestamp;
        }

        return $a['DisplayOrder'] <=> $b['DisplayOrder'];
    }
}
