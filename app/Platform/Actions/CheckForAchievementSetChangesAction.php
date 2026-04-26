<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementSet;

class CheckForAchievementSetChangesAction
{
    public function execute(AchievementSet $achievementSet): void
    {
        $latestVersion = $achievementSet->versions()->latest()->first();

        if ($latestVersion) {
            // update the volatile metrics so they're accurate at the point where a new version is created
            $latestVersion->players_total = $achievementSet->players_total ?? 0;
            $latestVersion->players_hardcore = $achievementSet->players_hardcore ?? 0;
            $latestVersion->achievements_unpublished = $achievementSet->achievements_unpublished ?? 0;

            if ($latestVersion->points_total === $achievementSet->points_total) {
                // if the points_total changed, the points_weighted value will have changed too,
                // and we don't want that reflected in the existing version - only in the new one.
                $latestVersion->points_weighted = $achievementSet->points_weighted ?? 0;
            }

            $latestVersion->save();

            if ($latestVersion->achievements_published === $achievementSet->achievements_published
                && $latestVersion->points_total === $achievementSet->points_total) {
                // no change to number of published achievements or points, just update the other fields.
                return;
            }

            // change detected, create a new version
        } elseif (!$achievementSet->achievements_published) {
            // don't bother versioning anything without published achievements
            return;
        }

        $achievementSet->versions()->create([
            'version' => $latestVersion ? $latestVersion->version + 1 : 1,
            'parent_id' => $latestVersion?->id,
            'players_total' => $achievementSet->players_total ?? 0,
            'players_hardcore' => $achievementSet->players_hardcore ?? 0,
            'achievements_published' => $achievementSet->achievements_published ?? 0,
            'achievements_unpublished' => $achievementSet->achievements_unpublished ?? 0,
            'points_total' => $achievementSet->points_total ?? 0,
            'points_weighted' => $achievementSet->points_weighted ?? 0,
            'created_at' => $achievementSet->updated_at,
        ]);
    }
}
