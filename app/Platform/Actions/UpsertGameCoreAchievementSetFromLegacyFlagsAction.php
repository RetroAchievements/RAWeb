<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementSet;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Platform\Enums\AchievementSetType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpsertGameCoreAchievementSetFromLegacyFlagsAction
{
    public function execute(Game $game): void
    {
        $game->load(['achievements', 'gameAchievementSets']);

        DB::transaction(function () use ($game) {
            $hasCoreSets = $game->gameAchievementSets()->core()->count() > 0;
            if ($hasCoreSets) {
                $this->updateExistingCoreSetOfGame($game);
            } else {
                $newAchievementSet = $this->createNewAchievementSetFromGame($game);
                $game->achievementSets()->attach($newAchievementSet->id, [
                    'type' => AchievementSetType::Core->value,
                    'order_column' => 0,

                    // Preserve the existing timestamps. Note that updated_at reuses the created_at
                    // value, as none of the fields stored in game_achievement_sets would've changed
                    // from the time of the first achievement promotion.
                    'created_at' => $newAchievementSet->created_at,
                    'updated_at' => $newAchievementSet->created_at,
                ]);
            }
        });
    }

    private function createNewAchievementSetFromGame(Game $game): AchievementSet
    {
        list($officialAchievements, $unofficialAchievements) = $game->achievements->partition(function ($achievement) {
            return $achievement->isPublished;
        });
        $allAchievements = $officialAchievements->merge($unofficialAchievements);

        // First, create the set.
        $achievementSet = AchievementSet::create([
            'achievements_published' => $officialAchievements->count(),
            'achievements_unpublished' => $unofficialAchievements->count(),
            'players_hardcore' => $game->players_hardcore,
            'players_total' => $game->players_total,
            'points_total' => $officialAchievements->sum('points'),
            'points_weighted' => $officialAchievements->sum('TrueRatio'),

            // Preserve the existing timestamps as best as we can.
            'created_at' => $allAchievements->min(fn ($achievement) => $achievement->DateCreated ?? now()),
            'updated_at' => $allAchievements->max(fn ($achievement) => $achievement->DateModified ?? now()),
        ]);

        // Next, attach all the achievements to the set.
        $allAchievements->each(function ($achievement) use ($achievementSet) {
            $achievementSet->achievements()->attach($achievement->ID, [
                'order_column' => $achievement->DisplayOrder,

                // Preserve the existing timestamps as best as we can.
                'created_at' => $achievement->DateCreated ?? now(),
                'updated_at' => $achievement->DateModified ?? now(),
            ]);
        });

        return $achievementSet;
    }

    private function updateExistingCoreSetOfGame(Game $game): void
    {
        // For now, we assume if a game has core sets, there's only one.
        // This may change in the future.
        /** @var AchievementSet $coreSet */
        $coreSet = $game->gameAchievementSets()->core()->first()->achievementSet;

        list($officialAchievements, $unofficialAchievements) = $game->achievements->partition(function ($achievement) {
            return $achievement->isPublished;
        });
        $allAchievements = $officialAchievements->merge($unofficialAchievements);

        // Update the core set with new totals.
        $coreSet->update([
            'achievements_published' => $officialAchievements->count(),
            'achievements_unpublished' => $unofficialAchievements->count(),
            'players_hardcore' => $game->players_hardcore,
            'players_total' => $game->players_total,
            'points_total' => $officialAchievements->sum('points'),
            'points_weighted' => $officialAchievements->sum('TrueRatio'),
            'updated_at' => now(),
        ]);

        // Resync the set's achievement timestamps data.
        // During this sync, newly-uploaded achievements to the server will also be attached.
        $syncData = $allAchievements->mapWithKeys(function ($achievement) {
            return [
                $achievement->ID => [
                    'created_at' => $achievement->DateCreated ?? now(),
                    'updated_at' => $achievement->DateModified ?? now(),
                    'order_column' => $achievement->DisplayOrder,
                ],
            ];
        });

        $this->syncAchievementSetAchievements($coreSet, $syncData);
    }

    /**
     * Sync the set's achievement_set_achievements entities with a single SQL query.
     *
     * @param Collection<int, array{created_at: Carbon|string, updated_at: Carbon|string, order_column: int}> $syncData
     */
    private function syncAchievementSetAchievements(AchievementSet $coreSet, Collection $syncData): void
    {
        if ($syncData->isEmpty()) {
            return;
        }

        $achievementIds = array_keys($syncData->toArray());

        // Delete achievements that are no longer in the set.
        AchievementSetAchievement::where('achievement_set_id', $coreSet->id)
            ->whereNotIn('achievement_id', $achievementIds)
            ->delete();

        // Prepare data for the upsert.
        $upsertData = [];
        foreach ($syncData as $achievementId => $pivotData) {
            $upsertData[] = [
                'achievement_set_id' => $coreSet->id,
                'achievement_id' => $achievementId,
                'order_column' => $pivotData['order_column'],
                'created_at' => $pivotData['created_at'],
                'updated_at' => $pivotData['updated_at'],
            ];
        }

        if (!empty($upsertData)) {
            AchievementSetAchievement::upsert(
                $upsertData,
                ['achievement_set_id', 'achievement_id'], // unique keys
                ['order_column', 'updated_at'] // columns to update if exists
            );
        }
    }
}
