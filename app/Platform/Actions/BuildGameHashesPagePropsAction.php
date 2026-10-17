<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Data\UserPermissionsData;
use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\User;
use App\Platform\Data\GameAchievementSetData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameHashData;
use App\Platform\Data\GameHashesPagePropsData;

class BuildGameHashesPagePropsAction
{
    public function __construct(
        private ResolveHashesForAchievementSetAction $resolveHashesAction,
    ) {
    }

    public function execute(
        Game $game,
        ?User $user,
        ?GameAchievementSet $targetAchievementSet = null,
    ): GameHashesPagePropsData {
        $game->loadMissing('hashes');

        return new GameHashesPagePropsData(
            game: GameData::fromGame($game)->include('badgeUrl', 'forumTopicId', 'system'),
            hashes: $this->buildCompatibleHashes($game, $targetAchievementSet),
            incompatibleHashes: $this->buildHashesByCompatibility($game, GameHashCompatibility::Incompatible),
            untestedHashes: $this->buildHashesByCompatibility($game, GameHashCompatibility::Untested),
            patchRequiredHashes: $this->buildHashesByCompatibility($game, GameHashCompatibility::PatchRequired),
            can: UserPermissionsData::fromUser($user)->include('manageGameHashes'),
            targetAchievementSet: $this->buildTargetAchievementSetData($targetAchievementSet),
        );
    }

    /**
     * @return GameHashData[]
     */
    private function buildCompatibleHashes(Game $game, ?GameAchievementSet $targetAchievementSet): array
    {
        return $this->resolveHashesAction->execute($game, $targetAchievementSet)
            ->map(fn (GameHash $hash) => GameHashData::fromGameHash($hash)->include('labels', 'patchUrl'))
            ->values()
            ->all();
    }

    /**
     * @return GameHashData[]
     */
    private function buildHashesByCompatibility(Game $game, GameHashCompatibility $compatibility): array
    {
        return $game->hashes
            ->where('compatibility', $compatibility)
            ->map(fn (GameHash $hash) => GameHashData::fromGameHash($hash)->include('labels', 'patchUrl'))
            ->values()
            ->all();
    }

    private function buildTargetAchievementSetData(?GameAchievementSet $targetAchievementSet): ?GameAchievementSetData
    {
        if (!$targetAchievementSet) {
            return null;
        }

        $this->ensureAchievementSetDefaults($targetAchievementSet);

        return GameAchievementSetData::from($targetAchievementSet)->include(
            'type',
            'title',
            'achievementSet.imageAssetPathUrl',
        );
    }

    /**
     * These fields may be null in the database but are required by the DTO.
     */
    private function ensureAchievementSetDefaults(GameAchievementSet $gameAchievementSet): void
    {
        $achievementSet = $gameAchievementSet->achievementSet;

        $achievementSet->median_time_to_complete ??= 0;
        $achievementSet->median_time_to_complete_hardcore ??= 0;
        $achievementSet->players_hardcore ??= 0;
        $achievementSet->players_total ??= 0;
    }
}
