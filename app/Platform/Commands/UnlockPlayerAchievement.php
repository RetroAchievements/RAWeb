<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UnlockPlayerAchievement as UnlockPlayerAchievementAction;
use App\Platform\Models\Achievement;
use App\Site\Models\User;
use Exception;
use Illuminate\Console\Command;

class UnlockPlayerAchievement extends Command
{
    protected $signature = 'ra:platform:player:unlock-achievement
                            {username}
                            {achievementIds : Comma-separated list of achievement IDs}
                            {--hardcore}';
    protected $description = 'Unlock achievement(s) for user';

    public function __construct(
        private readonly UnlockPlayerAchievementAction $unlockPlayerAchievement,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $username = $this->argument('username');
        $achievementIds = collect(explode(',', $this->argument('achievementIds')))
            ->map(fn ($id) => (int) $id);
        $hardcore = (bool) $this->option('hardcore');

        $user = User::where('User', $this->argument('username'))->firstOrFail();

        $achievements = Achievement::whereIn('id', $achievementIds)->get();

        $this->info('Unlocking ' . $achievements->count() . ' [' . ($hardcore ? 'hardcore' : 'softcore') . '] ' . __res('achievement', $achievements->count()) . ' for user [' . $username . '] [' . $user->id . ']');

        $progressBar = $this->output->createProgressBar($achievements->count());
        $progressBar->start();

        foreach ($achievements as $achievement) {
            $this->unlockPlayerAchievement->execute(
                $user,
                $achievement,
                $hardcore,
            );
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
