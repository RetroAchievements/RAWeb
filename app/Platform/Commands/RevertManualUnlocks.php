<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Actions\ResetPlayerProgressAction;
use Illuminate\Console\Command;

class RevertManualUnlocks extends Command
{
    protected $signature = 'ra:platform:player:revert-manual-unlocks
                            {--achievement-id= : Achievement ID to revert}
                            {--unlocker-id= : User ID of the unlocker whose manual unlocks should be reverted}
                            {--after-id= : Only revert unlocks with ID greater than this value}';
    protected $description = 'Revert all manual unlocks for a specific achievement made by a specific unlocker';

    public function __construct(
        private readonly ResetPlayerProgressAction $resetPlayerProgress,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $achievementId = $this->option('achievement-id');
        $unlockerId = $this->option('unlocker-id');

        if (!$achievementId || !$unlockerId) {
            $this->error('Both --achievement-id and --unlocker-id are required.');

            return;
        }

        $achievementId = (int) $achievementId;
        $unlockerId = (int) $unlockerId;

        // Find the unlocker user to display their username.
        $unlocker = User::withTrashed()->find($unlockerId);
        $unlockerDisplay = "[{$unlockerId}:{$unlocker->display_name}]";

        // Find all affected player achievements.
        $affectedAchievements = PlayerAchievement::where('achievement_id', $achievementId)
            ->where('unlocker_id', $unlockerId)
            ->with('user');

        // Filter by ID if provided.
        if ($afterId = $this->option('after-id')) {
            $affectedAchievements->where('id', '>', (int) $afterId);
        }

        $count = $affectedAchievements->count();

        if ($count === 0) {
            $this->info("No manual unlocks found for achievement {$achievementId} by unlocker {$unlockerDisplay}");

            return;
        }

        $filterInfo = $this->option('after-id') ? " (with ID > {$this->option('after-id')})" : "";
        $this->info("Found {$count} manual unlocks for achievement {$achievementId} by unlocker {$unlockerDisplay}{$filterInfo}");

        if (!$this->confirm('Do you want to revert these unlocks?')) {
            $this->info('Operation cancelled.');

            return;
        }

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        foreach ($affectedAchievements->cursor() as $achievement) {
            $this->resetPlayerProgress->execute(
                $achievement->user,
                $achievement->achievement_id
            );

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line(PHP_EOL);

        $this->info("Successfully reverted {$count} manual unlocks.");
    }
}
