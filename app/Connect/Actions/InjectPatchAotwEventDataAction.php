<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Models\EventAchievement;
use Illuminate\Support\Facades\Cache;

class InjectPatchAotwEventDataAction
{
    /**
     * Cache keys and TTL configuration for stale-while-revalidate pattern.
     * @see https://laravel.com/docs/11.x/cache#swr
     */
    private const CACHE_KEY_AOTW = 'aotw_achievement_data';
    private const CACHE_KEY_AOTM = 'aotm_achievement_data';
    private const CACHE_TTL_CONFIG = [30, 60]; // [fresh for 30s, stale but usable for 60s]

    /**
     * Add special achievement of the week labels to AOTW/AOTM achievement descriptions.
     *
     * @param array<string, mixed> $response Patch response data
     * @return array<string, mixed> modified response with AOTW/AOTM labels in descriptions
     */
    public function execute(array $response): array
    {
        $eventAchievements = $this->getEventAchievements();

        // If no event achievements were found, return an unmodified response.
        if (empty($eventAchievements)) {
            return $response;
        }

        // Update the main achievements list.
        if (!empty($response['PatchData']['Achievements'])) {
            $response['PatchData']['Achievements'] = $this->processAchievements(
                $response['PatchData']['Achievements'],
                $eventAchievements
            );
        }

        // Update the sets achievement list(s).
        if (!empty($response['PatchData']['Sets'])) {
            foreach ($response['PatchData']['Sets'] as &$set) {
                if (!empty($set['Achievements'])) {
                    $set['Achievements'] = $this->processAchievements(
                        $set['Achievements'],
                        $eventAchievements
                    );
                }
            }
        }

        return $response;
    }

    /**
     * Load the current AOTW and AOTM achievements.
     *
     * @return array<int, string> map of achievement IDs to their event labels
     */
    private function getEventAchievements(): array
    {
        $eventAchievements = [];

        $aotwAchievement = Cache::flexible(self::CACHE_KEY_AOTW, self::CACHE_TTL_CONFIG, function () {
            return EventAchievement::currentAchievementOfTheWeek()
                ->with(['achievement', 'sourceAchievement'])
                ->first();
        });
        $aotmAchievement = Cache::flexible(self::CACHE_KEY_AOTM, self::CACHE_TTL_CONFIG, function () {
            return EventAchievement::currentAchievementOfTheMonth()
                ->with(['achievement', 'sourceAchievement'])
                ->first();
        });

        // Build ID-to-label mappings.
        if ($aotwAchievement && $aotwAchievement->source_achievement_id) {
            $eventAchievements[$aotwAchievement->source_achievement_id] = '[Achievement of the Week] ';
        }
        if ($aotmAchievement && $aotmAchievement->source_achievement_id) {
            $eventAchievements[$aotmAchievement->source_achievement_id] = '[Achievement of the Month] ';
        }

        return $eventAchievements;
    }

    /**
     * Add event labels to achievements that match event IDs.
     *
     * @param array<int, array<string, mixed>> $achievements achievements to process
     * @param array<int, string> $eventAchievements map of achievement IDs to event labels
     * @return array<int, array<string, mixed>> processed achievements with labels
     */
    private function processAchievements(array $achievements, array $eventAchievements): array
    {
        return array_map(function ($achievement) use ($eventAchievements) {
            $achievementId = $achievement['ID'] ?? null;

            if ($achievementId && isset($eventAchievements[$achievementId])) {
                $achievement['Description'] = $eventAchievements[$achievementId] . $achievement['Description'];
            }

            return $achievement;
        }, $achievements);
    }
}
