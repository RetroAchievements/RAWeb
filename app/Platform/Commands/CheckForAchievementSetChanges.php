<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\AchievementSet;
use App\Models\GameAchievementSet;
use App\Platform\Actions\CheckForAchievementSetChangesAction;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;

class CheckForAchievementSetChanges extends Command
{
    protected $signature = 'ra:platform:game:check-for-changes
                            {gameId? : Process a single game by ID}
                            {--full : Check all games}';
    protected $description = 'Detects any versions achievement set changes';

    public function __construct(
        private readonly CheckForAchievementSetChangesAction $checkForChangesAction,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $singleGameId = $this->argument('gameId');
        if ($singleGameId) {
            $gameAchievementSet = GameAchievementSet::query()
                ->where('game_id', (int) $singleGameId)
                ->core()
                ->first();
            if ($gameAchievementSet) {
                $this->checkForChangesAction->execute($gameAchievementSet->achievementSet);
                $this->info('Done');
            } else {
                $this->error("Unknown game");
            }
        } else {
            if ($this->option('full')) {
                $query = AchievementSet::query()
                    ->where(function ($query) {
                        $query->where('achievements_published', '>', 0)
                            ->orWhereHas('versions');
                    });
            } else {
                $query = AchievementSet::query()
                    ->where('updated_at', '>', Carbon::now()->subHours(25));
            }

            $total = (clone $query)->count();
            $this->info("Processing {$total} achievement sets...");

            $bar = $this->output->createProgressBar($total);
            $bar->start();

            $query->with('achievements')->chunkById(100, function ($achievementSets) use ($bar) {
                foreach ($achievementSets as $achievementSet) {
                    $this->checkForChangesAction->execute($achievementSet);
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
        }
    }
}
