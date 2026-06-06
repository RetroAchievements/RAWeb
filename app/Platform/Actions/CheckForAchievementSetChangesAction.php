<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementSet;
use App\Models\AchievementSetVersion;
use App\Platform\Support\AchievementSetDefinition;

class CheckForAchievementSetChangesAction
{
    public function execute(AchievementSet $achievementSet): void
    {
        $latestVersion = $achievementSet->versions()->orderByDesc('version')->first();

        // an unversioned set with no published achievements isn't worth versioning - bail.
        if (!$latestVersion && !$achievementSet->achievements_published) {
            return;
        }

        $currentDefinition = (new BuildAchievementSetDefinitionAction())->execute($achievementSet);
        $projection = AchievementSetDefinition::publishedProjection($currentDefinition);
        $publishedCount = count($projection);
        $publishedPoints = array_sum($projection);
        $unpublishedCount = count($currentDefinition['achievements']) - $publishedCount;

        if ($latestVersion) {
            $publishedChanged = $this->didPublishedCompositionChange($latestVersion, $projection);

            $latestVersion->players_total = $achievementSet->players_total ?? 0;
            $latestVersion->players_hardcore = $achievementSet->players_hardcore ?? 0;
            $latestVersion->achievements_unpublished = $unpublishedCount;

            if (!$publishedChanged) {
                $latestVersion->definition = $currentDefinition;
                $latestVersion->save();

                return;
            }

            $latestVersion->save();
        }

        $achievementSet->versions()->create([
            'version' => $latestVersion ? $latestVersion->version + 1 : 1,
            'parent_id' => $latestVersion?->id,
            'definition' => $currentDefinition,
            'players_total' => $achievementSet->players_total ?? 0,
            'players_hardcore' => $achievementSet->players_hardcore ?? 0,
            'achievements_published' => $publishedCount,
            'achievements_unpublished' => $unpublishedCount,
            'points_total' => $publishedPoints,
            'created_at' => $achievementSet->updated_at,
        ]);
    }

    /**
     * Decide whether the published composition changed versus the latest version row. For
     * snapshotted rows the stored published projection (id => points) is authoritative: it is
     * order-stable, so equal projections mean an identical published set, while an equal-points
     * swap changes the id set and is correctly detected as a change.
     *
     * @param array<int, int> $projection the current published projection (id => points)
     */
    private function didPublishedCompositionChange(
        AchievementSetVersion $latestVersion,
        array $projection,
    ): bool {
        if ($latestVersion->definition === null) {
            return
                $latestVersion->achievements_published !== count($projection)
                || $latestVersion->points_total !== array_sum($projection);
        }

        return AchievementSetDefinition::publishedProjection($latestVersion->definition) !== $projection;
    }
}
