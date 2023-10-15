<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerMetrics as UpdatePlayerMetricsAction;
use App\Site\Models\User;
use Illuminate\Console\Command;

class UpdatePlayerMetrics extends Command
{
    protected $signature = 'ra:platform:player:update-metrics
                            {userId : User ID or username. Usernames containing only numbers are ambiguous and must be referenced by user ID}';
    protected $description = 'Update player metrics';

    public function __construct(
        private readonly UpdatePlayerMetricsAction $updatePlayerMetrics
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userId = $this->argument('userId');

        $user = is_numeric($userId)
            ? User::findOrFail($userId)
            : User::where('User', $userId)->firstOrFail();

        $this->info('Updating metrics for player [' . $user->username . '] [' . $user->id . ']');

        $this->updatePlayerMetrics->execute($user);
    }
}
