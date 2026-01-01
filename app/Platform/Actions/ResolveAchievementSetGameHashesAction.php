<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementSet;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Collection;

class ResolveAchievementSetGameHashesAction
{
    /**
     * Resolves all game hashes that could load the given achievement set.
     *
     * Functionally, this is the inverse of ResolveAchievementSetsAction.
     * ResolveAchievementSetsAction maps a hash to its sets.
     * This action maps a set to its hashes.
     *
     * @return Collection<int, GameHash>
     */
    public function execute(AchievementSet $achievementSet): Collection
    {
        $gameIds = $this->resolveGameIds($achievementSet->id);

        if (empty($gameIds)) {
            return collect();
        }

        // Exclude hashes someone explicitly marked as incompatible with this achievement set.
        $incompatibleHashIds = $achievementSet->incompatibleGameHashes()->pluck('game_hashes.id');

        return GameHash::whereIn('game_id', $gameIds)
            ->whereNotIn('id', $incompatibleHashIds)
            ->get();
    }

    /**
     * TODO Eventually, there should be no backing game IDs for achievement sets.
     *
     * At the moment, we're bound to game IDs due to `GameHash.game_id`.
     * To answer "which hashes can load this set?", we must:
     *   1. Find which games link to this set (via `GameAchievementSet`), and
     *   2. Find which hashes belong to those games (via `GameHash.game_id`).
     *
     * For a future where subsets don't have backing games, the schema will need
     * something like:
     *   a. A `GameHashAchievementSet` pivot table, OR
     *   b. An `achievement_set_id` or `set_type_flags` on `GameHash` (probably bad), OR
     *   c. Some other direct hash to achievement set relationship.
     * Until this schema change exists, we're technically constrained to go through game IDs.
     *
     * @return int[]
     */
    private function resolveGameIds(int $achievementSetId): array
    {
        $links = GameAchievementSet::where('achievement_set_id', $achievementSetId)->get();

        if ($links->isEmpty()) {
            return [];
        }

        $coreLink = $links->firstWhere('type', AchievementSetType::Core);

        // Specialty sets: only their backing game's hashes load them.
        // Base game hashes don't load specialty sets.
        if ($links->contains('type', AchievementSetType::Specialty)) {
            return $coreLink ? [$coreLink->game_id] : [];
        }

        // Exclusive sets: completely isolated, only use their backing game's hashes.
        if ($links->contains('type', AchievementSetType::Exclusive)) {
            return $coreLink ? [$coreLink->game_id] : [];
        }

        // Core and Bonus sets: find the base game and all games that link to it.
        // When loading a base game hash, core + bonus loaded.
        // When loading a bonus game hash, redirect to base game, core + bonus loaded.
        // When loading specialty game hash, redirect to base game, core + bonus + specialty loaded.
        $bonusLink = $links->firstWhere('type', AchievementSetType::Bonus);
        $baseGameId = $bonusLink?->game_id ?? $coreLink?->game_id;

        if (!$baseGameId) {
            return [];
        }

        $gameIds = [$baseGameId];

        $linkedSetIds = GameAchievementSet::where('game_id', $baseGameId)
            ->whereIn('type', [AchievementSetType::Bonus, AchievementSetType::Specialty])
            ->pluck('achievement_set_id');

        $linkedGameIds = GameAchievementSet::whereIn('achievement_set_id', $linkedSetIds)
            ->where('type', AchievementSetType::Core)
            ->pluck('game_id')
            ->toArray();

        return array_unique(array_merge($gameIds, $linkedGameIds));
    }
}
