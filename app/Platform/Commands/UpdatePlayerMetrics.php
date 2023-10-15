<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerMetrics as UpdatePlayerMetricsAction;
use App\Site\Models\User;
use Illuminate\Console\Command;

class UpdatePlayerMetrics extends Command
{
    protected $signature = 'ra:platform:player:update-metrics {username}';
    protected $description = 'Update player metrics';

    public function __construct(
        private readonly UpdatePlayerMetricsAction $updatePlayerMetrics
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $user = User::where('User', $this->argument('username'))->firstOrFail();

        $this->info('Updating metrics for player ' . $user->username . ' [' . $user->id . ']');

        $this->updatePlayerMetrics->execute($user);
    }
}
