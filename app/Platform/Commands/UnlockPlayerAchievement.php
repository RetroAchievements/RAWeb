<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use Exception;
use Illuminate\Console\Command;

class UnlockPlayerAchievement extends Command
{
    protected $signature = 'ra:platform:player:unlock-achievement
                            {userId : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}
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
        $userId = $this->argument('userId');
        $achievementIds = collect(explode(',', $this->argument('achievementIds')))
            ->map(fn ($id) => (int) $id);
        $hardcore = (bool) $this->option('hardcore');

        $user = is_numeric($userId)
            ? User::findOrFail($userId)
            : User::where('User', $userId)->firstOrFail();

        $achievements = Achievement::whereIn('id', $achievementIds)->get();

        $this->info('Unlocking ' . $achievements->count() . ' [' . ($hardcore ? 'hardcore' : 'softcore') . '] ' . __res('achievement', $achievements->count()) . ' for user [' . $user->id . ':' . $user->username . ']');

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
        $this->line(PHP_EOL);
    }
}
