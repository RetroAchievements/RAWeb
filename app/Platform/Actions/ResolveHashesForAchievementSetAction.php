<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Collection;

class ResolveHashesForAchievementSetAction
{
    /**
     * Returns hashes that will load the target achievement set.
     * If no target set is provided, returns hashes compatible with the game's core set.
     *
     * @return Collection<int, GameHash>
     */
    public function execute(Game $game, ?GameAchievementSet $targetSet): Collection
    {
        $game->loadMissing('hashes');

        // If no target set is specified, default to querying for hashes for the core set.
        if ($targetSet === null) {
            $coreSet = GameAchievementSet::where('game_id', $game->id)
                ->where('type', AchievementSetType::Core)
                ->with('achievementSet')
                ->first();

            if (!$coreSet) {
                $compatibleHashes = $game->hashes
                    ->where('compatibility', GameHashCompatibility::Compatible);

                return $this->sortHashes($compatibleHashes);
            }

            $targetSet = $coreSet;
        }

        // For core: core hashes + bonus hashes + specialty hashes.
        if ($targetSet->type === AchievementSetType::Core) {
            $allHashes = $this->getHashesForRootGameSet($game, $targetSet);

            $bonusAndSpecialtySets = GameAchievementSet::where('game_id', $game->id)
                ->whereIn('type', [AchievementSetType::Bonus, AchievementSetType::Specialty])
                ->with('achievementSet')
                ->get();

            foreach ($bonusAndSpecialtySets as $gas) {
                // Skip sets linked to multiple parent games. We can't determine
                // which backing hashes belong to this parent vs other parents.
                if ($this->isLinkedToMultipleParents($gas)) {
                    continue;
                }

                $backingHashes = $this->getBackingGameHashesForSet($gas);
                $allHashes = $allHashes->merge($backingHashes);
            }

            return $this->sortHashes($allHashes);
        }

        // For bonus: core hashes + bonus hashes.
        if ($targetSet->type === AchievementSetType::Bonus) {
            $mainGameHashes = $this->getHashesForRootGameSet($game, $targetSet);
            $backingGameHashes = $this->getBackingGameHashesForSet($targetSet);

            return $this->sortHashes($mainGameHashes->merge($backingGameHashes));
        }

        // For Specialty/Exclusive: backing game hashes only.
        return $this->sortHashes($this->getBackingGameHashesForSet($targetSet));
    }

    /**
     * @return Collection<int, GameHash>
     */
    private function getHashesForRootGameSet(Game $game, GameAchievementSet $targetSet): Collection
    {
        $compatibleHashes = $game->hashes
            ->where('compatibility', GameHashCompatibility::Compatible);

        return $this->filterOutIncompatibleHashes($compatibleHashes, $targetSet);
    }

    /**
     * Gets hashes from the backing game for a specialty/exclusive set.
     *
     * @return Collection<int, GameHash>
     */
    private function getBackingGameHashesForSet(GameAchievementSet $targetSet): Collection
    {
        // Find the legacy "backing game" where this set type is core.
        $coreLink = GameAchievementSet::where('achievement_set_id', $targetSet->achievement_set_id)
            ->where('type', AchievementSetType::Core)
            ->first();

        if (!$coreLink) {
            return collect();
        }

        $backingGame = Game::with('hashes')->find($coreLink->game_id);
        if (!$backingGame) {
            return collect();
        }

        $compatibleHashes = $backingGame->hashes
            ->where('compatibility', GameHashCompatibility::Compatible);

        return $this->filterOutIncompatibleHashes($compatibleHashes, $targetSet);
    }

    /**
     * @param Collection<int, GameHash> $hashes
     * @return Collection<int, GameHash>
     */
    private function filterOutIncompatibleHashes(Collection $hashes, GameAchievementSet $targetSet): Collection
    {
        $incompatibleIds = $targetSet->achievementSet
            ->incompatibleGameHashes()
            ->pluck('game_hashes.id');

        return $hashes->reject(fn (GameHash $hash) => $incompatibleIds->contains($hash->id));
    }

    /**
     * @param Collection<int, GameHash> $hashes
     * @return Collection<int, GameHash>
     */
    private function sortHashes(Collection $hashes): Collection
    {
        return $hashes->sort(function (GameHash $a, GameHash $b) {
            $aHasName = !empty(trim($a->name ?? ''));
            $bHasName = !empty(trim($b->name ?? ''));

            if ($aHasName !== $bHasName) {
                return $aHasName ? -1 : 1;
            }

            if ($aHasName && $bHasName) {
                return strcasecmp($a->name, $b->name);
            }

            return strcasecmp($a->md5, $b->md5);
        })->values();
    }

    /**
     * Checks if the given set is linked to multiple parent games.
     * For example, Pokemon Red & Blue are two distinct games that
     * share the same bonus subset.
     */
    private function isLinkedToMultipleParents(GameAchievementSet $set): bool
    {
        $nonCoreLinksCount = GameAchievementSet::where('achievement_set_id', $set->achievement_set_id)
            ->where('type', '!=', AchievementSetType::Core)
            ->count();

        return $nonCoreLinksCount > 1;
    }
}
