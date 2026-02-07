<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Enums\GameHashCompatibility;
use App\Models\AchievementAuthor;
use App\Models\AchievementMaintainer;
use App\Models\Game;
use App\Platform\Data\AggregateAchievementSetCreditsData;
use App\Platform\Data\UserCreditsData;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementSetAuthorTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildGameAggregateCreditsAction
{
    public function execute(Game $game): AggregateAchievementSetCreditsData
    {
        $achievementIds = $this->collectAchievementIds($game);

        $achievementSetArtworkCredits = collect();
        $achievementSetBannerCredits = collect();

        foreach ($game->gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;

            $this->accumulateSetAuthorCredit($achievementSet, AchievementSetAuthorTask::Artwork, $achievementSetArtworkCredits);
            $this->accumulateSetAuthorCredit($achievementSet, AchievementSetAuthorTask::Banner, $achievementSetBannerCredits);
        }

        $hashCompatibilityTestingCredits = $this->buildHashCompatibilityTestingCredits($game);
        $achievementsAuthors = $this->buildAchievementsAuthors($game);
        $achievementsMaintainers = $this->buildAchievementsMaintainers($achievementIds);
        $authorshipByTask = $this->buildAuthorshipByTask($achievementIds);

        return new AggregateAchievementSetCreditsData(
            achievementsAuthors: $this->toSortedUserCredits($achievementsAuthors, includeTrash: true),
            achievementsMaintainers: $this->toSortedUserCredits($achievementsMaintainers),
            achievementsArtwork: $this->toSortedUserCredits($authorshipByTask[AchievementAuthorTask::Artwork->value] ?? collect()),
            achievementsDesign: $this->toSortedUserCredits($authorshipByTask[AchievementAuthorTask::Design->value] ?? collect()),
            achievementSetArtwork: $this->toSortedUserCredits($achievementSetArtworkCredits),
            achievementSetBanner: $this->toSortedUserCredits($achievementSetBannerCredits),
            achievementsLogic: $this->toSortedUserCredits($authorshipByTask[AchievementAuthorTask::Logic->value] ?? collect()),
            achievementsTesting: $this->toSortedUserCredits($authorshipByTask[AchievementAuthorTask::Testing->value] ?? collect()),
            achievementsWriting: $this->toSortedUserCredits($authorshipByTask[AchievementAuthorTask::Writing->value] ?? collect()),
            hashCompatibilityTesting: $this->toSortedUserCredits($hashCompatibilityTestingCredits),
        );
    }

    /**
     * @return Collection<int, int>
     */
    private function collectAchievementIds(Game $game): Collection
    {
        return $game->gameAchievementSets
            ->pluck('achievementSet.achievements.*.id')
            ->flatten()
            ->unique()
            ->filter()
            ->values();
    }

    /**
     * Only the most recent author per task type receives credit for each achievement set.
     *
     * @param Collection<int, array<string, mixed>> $credits
     */
    private function accumulateSetAuthorCredit(
        mixed $achievementSet,
        AchievementSetAuthorTask $task,
        Collection $credits,
    ): void {
        $mostRecentAuthor = $achievementSet->achievementSetAuthors
            ->filter(fn ($author) => $author->task === $task)
            ->sortByDesc('created_at')
            ->first();

        if (!$mostRecentAuthor) {
            return;
        }

        $userId = $mostRecentAuthor->user_id;
        $existing = $credits->get($userId);

        $credits->put($userId, [
            'user' => $mostRecentAuthor->user,
            'count' => ($existing['count'] ?? 0) + 1,
            'created_at' => $mostRecentAuthor->created_at,
        ]);
    }

    /**
     * Credit users who tested hash compatibility (excluding the original hash submitter).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function buildHashCompatibilityTestingCredits(Game $game): Collection
    {
        $credits = collect();

        $compatibleHashes = $game->hashes()
            ->where('compatibility', GameHashCompatibility::Compatible)
            ->whereNotNull('compatibility_tester_id')
            ->whereColumn('compatibility_tester_id', '!=', 'user_id')
            ->with('compatibilityTester')
            ->get();

        foreach ($compatibleHashes as $hash) {
            if ($hash->compatibilityTester) {
                $credits->put($hash->compatibilityTester->id, [
                    'user' => $hash->compatibilityTester,
                    'count' => 0,
                    'created_at' => $hash->updated_at,
                ]);
            }
        }

        return $credits;
    }

    /**
     * Build credits for the original developer of each achievement.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function buildAchievementsAuthors(Game $game): Collection
    {
        $credits = collect();

        foreach ($game->gameAchievementSets as $gameAchievementSet) {
            foreach ($gameAchievementSet->achievementSet->achievements as $achievement) {
                if (!$achievement->developer) {
                    continue;
                }

                $userId = $achievement->developer->id;
                $credits->put($userId, [
                    'user' => $achievement->developer,
                    'count' => ($credits->get($userId)['count'] ?? 0) + 1,
                ]);
            }
        }

        return $credits;
    }

    /**
     * Build credits for active maintainers (excluding the original author).
     *
     * @param Collection<int, int> $achievementIds
     * @return Collection<int, array<string, mixed>>
     */
    private function buildAchievementsMaintainers(Collection $achievementIds): Collection
    {
        if ($achievementIds->isEmpty()) {
            return collect();
        }

        $credits = collect();

        $maintainerStats = AchievementMaintainer::query()
            ->join('achievements', 'achievement_maintainers.achievement_id', '=', 'achievements.id')
            ->whereIn('achievement_maintainers.achievement_id', $achievementIds)
            ->where('achievement_maintainers.is_active', true)
            ->whereColumn('achievement_maintainers.user_id', '!=', 'achievements.user_id')
            ->select('achievement_maintainers.user_id', DB::raw('COUNT(*) as count'), DB::raw('MAX(achievement_maintainers.effective_from) as latest_date'))
            ->with('user')
            ->groupBy('achievement_maintainers.user_id')
            ->get();

        foreach ($maintainerStats as $stat) {
            if ($stat->user && !$stat->user->trashed()) {
                $credits->put($stat->user_id, [
                    'user' => $stat->user,
                    'count' => $stat->count,
                    'created_at' => $stat->latest_date ? Carbon::parse($stat->latest_date) : null,
                ]);
            }
        }

        return $credits;
    }

    /**
     * Build per-task authorship credits (artwork, design, logic, testing, writing)
     * grouped by task type for easy lookup.
     *
     * @param Collection<int, int> $achievementIds
     * @return array<string, Collection<int, array<string, mixed>>>
     */
    private function buildAuthorshipByTask(Collection $achievementIds): array
    {
        if ($achievementIds->isEmpty()) {
            return [];
        }

        $byTask = [];

        $authorshipStats = AchievementAuthor::query()
            ->join('achievements', 'achievement_authors.achievement_id', '=', 'achievements.id')
            ->whereIn('achievement_authors.achievement_id', $achievementIds)
            ->whereColumn('achievement_authors.user_id', '!=', 'achievements.user_id')
            ->select('achievement_authors.user_id', 'achievement_authors.task', DB::raw('COUNT(*) as count'))
            ->with('user')
            ->groupBy('achievement_authors.user_id', 'achievement_authors.task')
            ->get();

        foreach ($authorshipStats as $stat) {
            if (!$stat->user || $stat->user->trashed()) {
                continue;
            }

            $taskKey = $stat->task;

            if (!isset($byTask[$taskKey])) {
                $byTask[$taskKey] = collect();
            }

            $byTask[$taskKey]->put($stat->user_id, [
                'user' => $stat->user,
                'count' => $stat->count,
            ]);
        }

        return $byTask;
    }

    /**
     * @param Collection<int, array<string, mixed>> $credits
     * @return UserCreditsData[]
     */
    private function toSortedUserCredits(Collection $credits, bool $includeTrash = false): array
    {
        return $credits
            ->filter(fn ($item) => $includeTrash || !$item['user']->trashed())
            ->sortByDesc('count')
            ->map(fn ($item) => UserCreditsData::fromUserWithCount(
                $item['user'],
                $item['count'],
                $item['created_at'] ?? null
            )->include('isGone'))
            ->values()
            ->all();
    }
}
