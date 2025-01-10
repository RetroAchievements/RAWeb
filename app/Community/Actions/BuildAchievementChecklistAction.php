<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\AchievementGroupData;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Data\AchievementData;

class BuildAchievementChecklistAction
{
    public function execute(
        string $encoded,
        User $user,
    ): array {
        $groups = [];
        foreach (explode('|', $encoded) as $group) {
            if (!empty($group)) {
                $groups[] = $this->parseGroup($group);
            }
        }

        return $this->fillData($groups, $user);
    }

    private function parseGroup(string $group): array
    {
        $index = strrpos($group, ':');
        if ($index === false) {
            $header = '';
            $ids = $group;
        } else {
            $header = substr($group, 0, $index);
            $ids = substr($group, $index + 1);
        }

        $achievementIds = [];
        foreach (explode(',', $ids) as $id) {
            $achievementIds[] = (int) $id;
        }

        return [
            'header' => $header,
            'achievementIds' => $achievementIds,
        ];
    }

    /**
     * @return AchievementGroupData[]
     */
    private function fillData(array $groups, User $user): array
    {
        $ids = [];
        foreach ($groups as $group) {
            $ids = array_merge($ids, $group['achievementIds']);
        }
        $ids = array_unique($ids);

        $achievements = Achievement::whereIn('ID', $ids)->with('game')->get();
        $unlocks = PlayerAchievement::where('user_id', $user->id)->whereIn('achievement_id', $ids)->get();

        $result = [];
        foreach ($groups as $group) {
            $achievementList = [];
            foreach ($group['achievementIds'] as $achievementId) {
                $achievement = $achievements->filter(fn ($a) => $a->ID === $achievementId)->first();
                if ($achievement) {
                    $unlock = $unlocks->filter(fn ($u) => $u->achievement_id === $achievementId)->first();
                    $achievementList[] = AchievementData::from($achievement, $unlock)->include(
                        'description',
                        'points',
                        'badgeUnlockedUrl',
                        'badgeLockedUrl',
                        'unlockedAt',
                        'unlockedHardcoreAt',
                        'game.badgeUrl',
                    );
                }
            }

            $result[] = new AchievementGroupData($group['header'], $achievementList);
        }

        return $result;
    }
}
