<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ClaimStatus;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\AchievementType;
use Illuminate\Support\Collection;

class LoadGameWithRelationsAction
{
    /**
     * Efficiently load a game for the game show page with all its required relations.
     *
     * @param Game $game the game to load relations for
     * @param bool $isPromoted whether to load published or unpublished assets
     * @param GameAchievementSet|null $targetAchievementSet if provided, only load this specific achievement set
     * @return Game the game with properly loaded relations
     */
    public function execute(Game $game, bool $isPromoted = true, ?GameAchievementSet $targetAchievementSet = null): Game
    {
        // First, load all the missing relations.
        $game->loadMissing([
            'achievementSetClaims' => function ($query) {
                $query->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview])
                    ->with('user');
            },
            'gameAchievementSets' => function ($query) use ($targetAchievementSet) {
                if ($targetAchievementSet !== null) {
                    // If a specific achievement set is requested, only load that one.
                    $query->where('achievement_set_id', $targetAchievementSet->achievement_set_id);
                } else {
                    // Otherwise, only load core sets.
                    // We won't rule out the possibility a game has multiple core sets,
                    // though this should be exceedingly rare (if ever).
                    $query->where('type', AchievementSetType::Core);
                }
            },
            'hashes',
            'hubs' => function ($query) {
                $query->with(['viewRoles']);
            },
            'leaderboards' => function ($query) {
                $query->where('order_column', '>=', 0) // only show visible leaderboards on the page
                    ->orderBy('order_column')
                    ->with(['topEntry.user']);
            },
            'releases',
            'visibleComments' => function ($query) {
                $query->latest('created_at')
                    ->limit(20)
                    ->with(['user' => function ($userQuery) {
                        $userQuery->withTrashed();
                    }]);
            },
        ]);

        // Then, load the related achievements for the filtered sets.
        $game->gameAchievementSets->load([
            'achievementSet.achievements' => function ($query) use ($isPromoted) {
                $query->where('is_promoted', $isPromoted);
            },

            'achievementSet.achievements.developer',
            'achievementSet.achievementGroups' => fn ($query) => $query->withCount([
                'achievements' => fn ($q) => $q->where('is_promoted', $isPromoted),
            ]),
            'achievementSet.achievementSetAuthors.user',
        ]);

        $this->computeGroupRepresentativeBadges($game);

        // Load all selectable achievement sets for navigation purposes only.
        // We'll pass this along as a custom attribute for the props building action.
        $game->setAttribute('selectableGameAchievementSets', $game->selectableGameAchievementSets()->get());

        return $game;
    }

    /**
     * Compute and attach representative badge URLs for each achievement group.
     *
     * Priority rules:
     * 1. The last win condition achievement in the list.
     * 2. (if no win conditions) The last progression achievement in the list.
     * 3. (if no progression achievements) The highest value achievement (by points) in the list.
     * 4. (if all the same points) The last achievement in the list.
     */
    private function computeGroupRepresentativeBadges(Game $game): void
    {
        foreach ($game->gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;
            $achievements = $achievementSet->achievements;

            if ($achievements->isEmpty()) {
                continue;
            }

            // Group achievements by their group ID.
            $achievementsByGroup = $achievements->groupBy(fn (Achievement $a) => $a->pivot->achievement_group_id ?? -1);

            foreach ($achievementSet->achievementGroups as $group) {
                $groupAchievements = $achievementsByGroup->get($group->id, collect());
                $representative = $this->findRepresentativeAchievement($groupAchievements);
                $group->representative_badge_url = $representative?->badge_unlocked_url;
            }

            // Also compute the representative badge for ungrouped achievements.
            $ungroupedAchievements = $achievementsByGroup->get(-1, collect());
            $ungroupedRepresentative = $this->findRepresentativeAchievement($ungroupedAchievements);
            $achievementSet->ungrouped_badge_url = $ungroupedRepresentative?->badge_unlocked_url;
        }
    }

    /**
     * Find the representative achievement for a group based on priority rules.
     *
     * @param Collection<int, Achievement> $achievements
     */
    private function findRepresentativeAchievement(Collection $achievements): ?Achievement
    {
        if ($achievements->isEmpty()) {
            return null;
        }

        $sortedAchievements = $achievements->sortBy(fn (Achievement $a) => $a->pivot->order_column ?? $a->order_column);

        // Priority 1: Last win condition achievement.
        $winConditions = $sortedAchievements->filter(fn (Achievement $a) => $a->type === AchievementType::WinCondition);
        if ($winConditions->isNotEmpty()) {
            return $winConditions->last();
        }

        // Priority 2: Last progression achievement.
        $progressions = $sortedAchievements->filter(fn (Achievement $a) => $a->type === AchievementType::Progression);
        if ($progressions->isNotEmpty()) {
            return $progressions->last();
        }

        // Priority 3: Highest value achievement (by points).
        $maxPoints = $sortedAchievements->max(fn (Achievement $a) => $a->points);
        $highestPointAchievements = $sortedAchievements->filter(fn (Achievement $a) => $a->points === $maxPoints);

        if ($highestPointAchievements->count() === 1) {
            return $highestPointAchievements->first();
        }

        // Priority 4: If they're all same points (or multiple with the highest), return the last achievement in the list.
        return $sortedAchievements->last();
    }
}
