<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Collection;

class ResolveAchievementSetsAction
{
    /**
     * Resolves the achievement sets for a given game hash and user.
     *
     * @return Collection<int, GameAchievementSet>
     */
    public function execute(GameHash $gameHash, User $user): Collection
    {
        $game = $gameHash->game;

        $initialSet = GameAchievementSet::where('game_id', $game->id)->first();
        if (!$initialSet) {
            return collect();
        }

        // Determine the current game context based on the initial set and game.
        [$gameIdToUse, $typesToLoad] = $this->buildResolutionContext($initialSet, $game->id);

        // Retrieve all relevant achievement sets for the current context.
        $allSets = $this->getAllAchievementSets($gameIdToUse, $typesToLoad);

        // Fetch user opt-in/out preferences for these sets.
        $userSetPreferences = $this->getUserSetPreferences($user, $allSets);

        // Filter the sets based on preferences and hash compatibility.
        $filteredSets = $this->filterSets($allSets, $gameHash, $user, $userSetPreferences);

        // Sort the sets based on their type priority.
        $sortedSets = $this->sortSets($filteredSets);

        return $sortedSets->values();
    }

    /**
     * Given a GameAchievementSet, returns the set's game ID and the types to load.
     *
     * @return array [int $gameIdToUse, array|null $typesToLoad]
     */
    private function buildResolutionContext(GameAchievementSet $initialSet, int $defaultGameId): array
    {
        $links = GameAchievementSet::where('achievement_set_id', $initialSet->achievement_set_id)->get();

        $exclusiveLink = $links->firstWhere('type', AchievementSetType::Exclusive);
        $specialtyLink = $links->firstWhere('type', AchievementSetType::Specialty);
        $bonusLink = $links->firstWhere('type', AchievementSetType::Bonus);

        if ($exclusiveLink !== null) {
            // Exclusive set: only load the exclusive set.
            return [$exclusiveLink->game_id, [AchievementSetType::Exclusive]];
        }

        if ($specialtyLink !== null) {
            // Specialty set: load all sets from the base game except exclusive.
            $typesToLoad = [
                AchievementSetType::Core,
                AchievementSetType::Bonus,
                AchievementSetType::Specialty,
            ];

            return [$specialtyLink->game_id, $typesToLoad];
        }

        if ($bonusLink !== null) {
            // Bonus set: load core and bonus sets from the linked game.
            return [$bonusLink->game_id, [AchievementSetType::Core, AchievementSetType::Bonus]];
        }

        // Core set: load core and bonus sets.
        return [$defaultGameId, [AchievementSetType::Core, AchievementSetType::Bonus]];
    }

    /**
     * Retrieves all relevant achievement sets based on game ID and types.
     *
     * @return Collection<int, GameAchievementSet>
     */
    private function getAllAchievementSets(int $gameId, ?array $types): Collection
    {
        $query = GameAchievementSet::with([
            'achievementSet' => [
                'achievements' => fn ($q) => $q->orderBy('DisplayOrder'),
                'incompatibleGameHashes',
            ],
        ])->where('game_id', $gameId);

        if ($types !== null) {
            $query->whereIn('type', $types);
        }

        return $query->get();
    }

    /**
     * Fetches user preferences for the given achievement sets.
     *
     * @param Collection<int, GameAchievementSet> $allSets
     * @return Collection<int, UserGameAchievementSetPreference>
     */
    private function getUserSetPreferences(User $user, Collection $allSets): Collection
    {
        return UserGameAchievementSetPreference::where('user_id', $user->id)
            ->whereIn('game_achievement_set_id', $allSets->pluck('id'))
            ->get()
            ->keyBy('game_achievement_set_id');
    }

    /**
     * Filter the achievement sets based on user preferences and hash compatibility.
     *
     * @param Collection<int, GameAchievementSet> $allSets
     * @param Collection<int, UserGameAchievementSetPreference> $userSetPreferences
     * @return Collection<int, GameAchievementSet>
     */
    private function filterSets(
        Collection $allSets,
        GameHash $gameHash,
        User $user,
        Collection $userSetPreferences
    ): Collection {
        return $allSets->filter(function (GameAchievementSet $set) use ($gameHash, $user, $userSetPreferences) {
            // Exclude if this hash is marked as incompatible
            if ($set->achievementSet->incompatibleGameHashes->contains('id', $gameHash->id)) {
                return false;
            }

            // Check user-specific preferences
            $preference = $userSetPreferences->get($set->id);
            if ($preference !== null) {
                return $preference->opted_in;
            }

            // Apply global preference for non-core sets
            if ($set->type !== AchievementSetType::Core) {
                return !$user->is_globally_opted_out_of_subsets;
            }

            // Include core sets by default
            return true;
        });
    }

    /**
     * Sort the achievement sets based on their type priority.
     *
     * @param Collection<int, GameAchievementSet> $sets
     * @return Collection<int, GameAchievementSet>
     */
    private function sortSets(Collection $sets): Collection
    {
        return $sets->sortBy(function (GameAchievementSet $set) {
            return match ($set->type) {
                AchievementSetType::Exclusive => 0,
                AchievementSetType::Core => 1,
                AchievementSetType::Bonus => 2,
                AchievementSetType::Specialty => 3,
                default => 4,
            };
        });
    }
}
