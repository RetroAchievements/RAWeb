<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;

class GameUserAchievementsGridService
{
    public function getGameAchievementsWithUserProgress(Game $game, User $user): array
    {
        $gameAchievements = $game->achievements()
            ->orderBy('DisplayOrder')
            ->published()
            ->get();

        $userUnlocks = getUserAchievementUnlocksForGame($user, $game->id, AchievementFlag::OfficialCore);

        $entities = [];
        foreach ($gameAchievements as $gameAchievement) {
            $badgeName = $gameAchievement->BadgeName . '_lock';

            $entities[$gameAchievement->id] = [
                'ID' => $gameAchievement->id,
                'Title' => $gameAchievement->title,
                'Description' => $gameAchievement->description,
                'Points' => $gameAchievement->points,
                'TrueRatio' => $gameAchievement->points_weighted,
                'Type' => $gameAchievement->type,
                'BadgeName' => $badgeName,
                'BadgeURL' => media_asset("Badge/{$badgeName}.png"),
                'BadgeClassNames' => '',
                'DisplayOrder' => $gameAchievement->DisplayOrder,
                'Unlocked' => false,
                'DateAwarded' => null,
            ];
        }
        foreach ($userUnlocks as $achievementId => $userUnlock) {
            if (array_key_exists($achievementId, $entities)) {
                $entities[$achievementId]['BadgeName'] = str_replace('_lock', '', $entities[$achievementId]['BadgeName']);
                $entities[$achievementId]['BadgeURL'] = str_replace('_lock', '', $entities[$achievementId]['BadgeURL']);
                $entities[$achievementId]['Unlocked'] = true;

                if (array_key_exists('DateEarnedHardcore', $userUnlock)) {
                    $entities[$achievementId]['HardcoreAchieved'] = $userUnlock['DateEarnedHardcore'];
                    $entities[$achievementId]['BadgeClassNames'] = 'goldimage';
                }
                if (array_key_exists('DateEarned', $userUnlock)) {
                    $entities[$achievementId]['DateAwarded'] = $userUnlock['DateEarned'];
                }
            }
        }

        // Unlocked achievements will appear before locked ones.
        // The unlocked achievements will be sorted by unlock date.
        // The locked achievements will be sorted by display order.
        usort($entities, function ($a, $b) {
            if ($a['Unlocked'] === $b['Unlocked']) {
                if ($a['Unlocked']) {
                    // Both are unlocked, sort by DateAwarded.
                    return strtotime($b['DateAwarded']) <=> strtotime($a['DateAwarded']);
                } else {
                    // Both are locked, sort by DisplayOrder.
                    return $a['DisplayOrder'] <=> $b['DisplayOrder'];
                }
            }

            return $a['Unlocked'] ? -1 : 1;
        });

        return $entities;
    }
}
