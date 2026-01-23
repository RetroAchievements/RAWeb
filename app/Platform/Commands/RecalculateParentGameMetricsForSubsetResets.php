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

class RecalculateParentGameMetricsForSubsetResets extends Command
{
    protected $signature = 'ra:platform:recalculate-parent-game-metrics-for-subset-resets';
    protected $description = 'Fixes time_taken on parent games where users reset a bonus/specialty subset game';

    private const SUBSET_TYPES = [AchievementSetType::Bonus, AchievementSetType::Specialty];

    public function handle(): void
    {
        $parentGameIds = GameAchievementSet::whereIn('type', self::SUBSET_TYPES)
            ->pluck('game_id')
            ->unique();

        $subsetAchievementSetIds = GameAchievementSet::whereIn('type', self::SUBSET_TYPES)
            ->pluck('achievement_set_id')
            ->unique();

        $subsetGameIds = GameAchievementSet::whereIn('achievement_set_id', $subsetAchievementSetIds)
            ->where('type', AchievementSetType::Core)
            ->pluck('game_id')
            ->unique();

        $usersWithSubsetResets = PlayerProgressReset::where('type', PlayerProgressResetType::Game)
            ->whereIn('type_id', $subsetGameIds)
            ->pluck('user_id')
            ->unique();

        $this->info("Found {$usersWithSubsetResets->count()} users who reset subset games.");
        $this->info("Found {$parentGameIds->count()} parent games with subsets.");

        $query = PlayerGame::query()
            ->whereIn('user_id', $usersWithSubsetResets)
            ->whereIn('game_id', $parentGameIds);

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No affected records found.');

            return;
        }

        $this->info("Dispatching {$totalCount} jobs...");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $dispatched = 0;
        $query->select(['user_id', 'game_id'])->chunk(1000, function ($records) use (&$dispatched, $bar): void {
            foreach ($records as $record) {
                dispatch(new UpdatePlayerGameMetricsJob($record->user_id, $record->game_id))
                    ->onQueue('player-game-metrics');

                $dispatched++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $this->info("Dispatched {$dispatched} jobs.");
    }
}
