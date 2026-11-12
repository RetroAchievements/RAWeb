<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Platform\Actions\UpdateDemotedAchievementPlayerMetricsAction;
use Illuminate\Console\Command;

/**
 * @deprecated delete after a one-time run in prod
 */
class UpdateDemotedAchievementPlayerMetrics extends Command
{
    protected $signature = 'ra:platform:achievement:update-demoted-player-metrics';
    protected $description = 'Queue player metrics updates for each player who should retain points from demoted achievements';

    public function __construct(
        private readonly UpdateDemotedAchievementPlayerMetricsAction $action,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $demotedAchievements = Achievement::query()
            ->where('should_keep_demoted_points', true)
            ->where('points', '>', 0);

        $demotedAchievementCount = $demotedAchievements->count();

        $this->info("Queueing player metrics updates for {$demotedAchievementCount} demoted achievements.");

        $queueProgress = $this->output->createProgressBar($demotedAchievementCount);

        $demotedAchievements->lazyById()->each(function (Achievement $demotedAchievement) use ($queueProgress): void {
            $this->action->execute($demotedAchievement);
            $queueProgress->advance();
        });

        $queueProgress->finish();
        $this->newLine();
    }
}
