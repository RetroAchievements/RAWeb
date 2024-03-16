<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Achievement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class UpdateAchievementsUserIds extends Command
{
    protected $signature = 'ra:platform:achievement:update-user-ids
                            {achievementId?}';
    protected $description = 'Update user IDs in the Achievements table. This is a precursor to dropping the User column from the table.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $achievementId = $this->argument('achievementId');

        if ($achievementId !== null) {
            $achievement = Achievement::findOrFail($achievementId);

            $this->info('Updating user ID for achievement [' . $achievement->id . ']');

            $foundUser = User::where('User', $achievement->Author)->first();
            if ($foundUser) {
                $achievement->user_id = $foundUser->id;
                $achievement->save();
            }
        } else {
            $this->info('Updating user IDs for all achievements.');

            $totalCount = Achievement::whereNull('user_id')->count();
            $progressBar = $this->output->createProgressBar($totalCount);
            $progressBar->start();

            Achievement::whereNull('user_id')
                ->chunkById(1000, function (Collection $chunk) use ($progressBar) {
                    $chunk->each(function ($achievement) {
                        $foundUser = User::where('User', $achievement->Author)->first();
                        if ($foundUser) {
                            $achievement->user_id = $foundUser->id;
                            $achievement->save();
                        }
                    });

                    $progressBar->advance(1000);
                });

            $progressBar->finish();
        }
    }
}
