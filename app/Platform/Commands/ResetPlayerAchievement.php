<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Actions\ResetPlayerProgressAction;
use Exception;
use Illuminate\Console\Command;

class ResetPlayerAchievement extends Command
{
    protected $signature = 'ra:platform:player:reset-achievement
                            {userId : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}
                            {achievementIds : Comma-separated list of achievement IDs}';
    protected $description = 'Reset achievement(s) for user';

    public function __construct(
        private readonly ResetPlayerProgressAction $resetPlayerProgress,
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

        $user = is_numeric($userId)
            ? User::findOrFail($userId)
            : User::where('User', $userId)->firstOrFail();

        $achievements = PlayerAchievement::where('user_id', $user->id)
            ->whereIn('achievement_id', $achievementIds);

        $this->info('Resetting ' . $achievements->count() . ' ' . __res('achievement', $achievements->count()) . ' for user [' . $user->id . ':' . $user->username . ']');

        $progressBar = $this->output->createProgressBar($achievements->count());
        $progressBar->start();

        foreach ($achievements->get() as $achievement) {
            $this->resetPlayerProgress->execute(
                $user,
                $achievement->achievement_id
            );
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line(PHP_EOL);
    }
}
