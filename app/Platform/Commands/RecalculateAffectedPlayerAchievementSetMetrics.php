<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RecalculateAffectedPlayerAchievementSetMetrics extends Command
{
    protected $signature = 'ra:platform:recalculate-affected-player-achievement-set-metrics';
    protected $description = 'Recalculates PlayerAchievementSet metrics for users affected by the multiset time_taken bug';

    private const AFFECTED_TYPES = [AchievementSetType::Bonus->value, AchievementSetType::Specialty->value];
    private const START_DATE = '2025-05-01';

    public function handle(): void
    {
        $nonCoreSetIds = $this->getNonCoreAchievementSetIds();
        $this->info('Found ' . count($nonCoreSetIds) . ' non-core achievement sets.');

        $totalCount = $this->buildAffectedRecordsQuery($nonCoreSetIds)->count();

        if ($totalCount === 0) {
            $this->info('No affected records found.');

            return;
        }

        $this->info("Found {$totalCount} unique user/game combinations to process.");

        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $dispatched = 0;

        $this->buildAffectedRecordsQuery($nonCoreSetIds)
            ->orderBy('player_achievement_sets.user_id')
            ->orderBy('game_achievement_sets.game_id')
            ->chunk(1000, function ($records) use (&$dispatched, $bar): void {
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

    /**
     * @return array<int, int>
     */
    private function getNonCoreAchievementSetIds(): array
    {
        return GameAchievementSet::whereIn('type', self::AFFECTED_TYPES)
            ->pluck('achievement_set_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, int> $nonCoreSetIds
     * @return Builder<PlayerAchievementSet>
     */
    private function buildAffectedRecordsQuery(array $nonCoreSetIds): Builder
    {
        return PlayerAchievementSet::query()
            ->whereIn('player_achievement_sets.achievement_set_id', $nonCoreSetIds)
            ->where('player_achievement_sets.updated_at', '>=', self::START_DATE)
            ->join('game_achievement_sets', function ($join): void {
                $join->on('player_achievement_sets.achievement_set_id', '=', 'game_achievement_sets.achievement_set_id')
                    ->whereIn('game_achievement_sets.type', self::AFFECTED_TYPES);
            })
            ->select('player_achievement_sets.user_id', 'game_achievement_sets.game_id')
            ->distinct();
    }
}
