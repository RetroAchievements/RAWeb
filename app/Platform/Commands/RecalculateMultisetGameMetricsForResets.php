<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\GameAchievementSet;
use App\Models\PlayerGame;
use App\Models\PlayerProgressReset;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\PlayerProgressResetType;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RecalculateMultisetGameMetricsForResets extends Command
{
    protected $signature = 'ra:platform:recalculate-multiset-game-metrics-for-resets';
    protected $description = 'Fixes time_taken on multiset games where users reset either the parent or a subset game';

    private const SUBSET_TYPES = [AchievementSetType::Bonus, AchievementSetType::Specialty];

    public function handle(): void
    {
        [$parentToSubsets, $subsetToParent] = $this->buildGameMappings();

        $parentGameIds = array_keys($parentToSubsets);
        $subsetGameIds = array_keys($subsetToParent);

        $this->info("Found " . count($parentGameIds) . " parent games with subsets.");
        $this->info("Found " . count($subsetGameIds) . " subset backing games.");

        $recalculationPairs = collect();

        PlayerProgressReset::where('type', PlayerProgressResetType::Game)
            ->whereIn('type_id', $subsetGameIds)
            ->select(['user_id', 'type_id'])
            ->each(function ($reset) use ($subsetToParent, $recalculationPairs): void {
                $recalculationPairs->push([
                    'user_id' => $reset->user_id,
                    'game_id' => $subsetToParent[$reset->type_id],
                ]);
            });

        PlayerProgressReset::where('type', PlayerProgressResetType::Game)
            ->whereIn('type_id', $parentGameIds)
            ->select(['user_id', 'type_id'])
            ->each(function ($reset) use ($parentToSubsets, $recalculationPairs): void {
                foreach ($parentToSubsets[$reset->type_id] as $subsetId) {
                    $recalculationPairs->push([
                        'user_id' => $reset->user_id,
                        'game_id' => $subsetId,
                    ]);
                }
            });

        $recalculationPairs = $recalculationPairs->unique(
            fn ($pair) => $pair['user_id'] . '-' . $pair['game_id']
        );

        $this->info("Found {$recalculationPairs->count()} potential recalculations needed.");

        $existingPairs = $this->filterToExistingPlayerGames($recalculationPairs);

        if ($existingPairs->isEmpty()) {
            $this->info('No affected records found.');

            return;
        }

        $this->info("Dispatching {$existingPairs->count()} jobs...");

        $bar = $this->output->createProgressBar($existingPairs->count());
        $bar->start();

        foreach ($existingPairs as $pair) {
            dispatch(new UpdatePlayerGameMetricsJob($pair['user_id'], $pair['game_id']))
                ->onQueue('player-game-metrics');

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Dispatched {$existingPairs->count()} jobs.");
    }

    /**
     * @return array{0: array<int, array<int>>, 1: array<int, int>} [parentToSubsets, subsetToParent]
     */
    private function buildGameMappings(): array
    {
        $parentToSubsets = [];
        $subsetToParent = [];

        $subsetLinks = GameAchievementSet::whereIn('type', self::SUBSET_TYPES)
            ->select(['game_id', 'achievement_set_id'])
            ->get();

        $achievementSetIds = $subsetLinks->pluck('achievement_set_id')->unique();

        $coreGames = GameAchievementSet::whereIn('achievement_set_id', $achievementSetIds)
            ->where('type', AchievementSetType::Core)
            ->select(['achievement_set_id', 'game_id'])
            ->get()
            ->keyBy('achievement_set_id');

        foreach ($subsetLinks as $link) {
            $coreGame = $coreGames[$link->achievement_set_id] ?? null;
            if (!$coreGame) {
                continue;
            }

            $parentId = $link->game_id;
            $subsetGameId = $coreGame->game_id;

            $parentToSubsets[$parentId][] = $subsetGameId;
            $subsetToParent[$subsetGameId] = $parentId;
        }

        return [$parentToSubsets, $subsetToParent];
    }

    /**
     * @param Collection<int, array{user_id: int, game_id: int}> $pairs
     * @return Collection<int, array{user_id: int, game_id: int}>
     */
    private function filterToExistingPlayerGames(Collection $pairs): Collection
    {
        $existingPairs = collect();

        $pairs->chunk(1000)->each(function (Collection $chunk) use ($existingPairs): void {
            $existingKeys = PlayerGame::whereIn('user_id', $chunk->pluck('user_id')->unique())
                ->whereIn('game_id', $chunk->pluck('game_id')->unique())
                ->select(['user_id', 'game_id'])
                ->get()
                ->mapWithKeys(fn ($pg) => [$pg->user_id . '-' . $pg->game_id => true]);

            foreach ($chunk as $pair) {
                $key = $pair['user_id'] . '-' . $pair['game_id'];
                if (isset($existingKeys[$key])) {
                    $existingPairs->push($pair);
                }
            }
        });

        return $existingPairs;
    }
}
