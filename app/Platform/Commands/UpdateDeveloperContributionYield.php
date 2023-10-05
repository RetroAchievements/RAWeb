<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdateDeveloperContributionYield as UpdateDeveloperContributionYieldAction;
use App\Site\Models\User;
use Illuminate\Console\Command;

class UpdateDeveloperContributionYield extends Command
{
    protected $signature = 'ra:platform:developer:update-contribution-yield {username?}';
    protected $description = 'Calculate developer contributions and badge tiers';

    public function __construct(
        private readonly UpdateDeveloperContributionYieldAction $updateDeveloperContributionYield
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $username = $this->argument('username');

        $users = collect();

        if (!empty($username)) {
            $users->push(User::where('User', $username)->firstOrFail());
        } else {
            $users = User::select('User')
                ->where('ContribCount', '>', 0)
                ->get();
        }

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        /** @var User $user */
        foreach ($users as $user) {
            $this->updateDeveloperContributionYield->execute($user);
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
