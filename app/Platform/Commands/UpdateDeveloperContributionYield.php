<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\User;
use App\Platform\Jobs\UpdateDeveloperContributionYieldJob;
use Illuminate\Console\Command;

class UpdateDeveloperContributionYield extends Command
{
    protected $signature = 'ra:platform:developer:update-contribution-yield {username?}';
    protected $description = 'Calculate developer contributions and badge tiers';

    public function handle(): void
    {
        $username = $this->argument('username');

        if (!empty($username)) {
            $users = User::whereName($username)->get();
        } else {
            $users = User::where('ContribCount', '>', 0)->get();
        }

        $this->info("Dispatching {$users->count()} developer contribution yield update jobs...");
        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        /** @var User $user */
        foreach ($users as $user) {
            dispatch(new UpdateDeveloperContributionYieldJob($user->id))
                ->onQueue('developer-metrics');

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line(PHP_EOL);
    }
}
