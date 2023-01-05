<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Exception;
use Illuminate\Console\Command;

class UnlockPlayerAchievement extends Command
{
    protected $signature = 'ra:platform:player:unlock-achievement';
    protected $description = '';

    public function __construct(
        // private UnlockPlayerAchievementAction $unlockPlayerAchievementAction
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        // $this->unlockPlayerAchievementAction->execute($user, $achievement, $hardcore, $unlockedBy = null);
    }
}
