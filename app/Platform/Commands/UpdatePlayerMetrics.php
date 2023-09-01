<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Platform\Actions\UpdatePlayerMetricsAction;
use App\Site\Models\User;
use Illuminate\Console\Command;

class UpdatePlayerMetrics extends Command
{
    protected $signature = 'ra:platform:player:update-metrics {username}';
    protected $description = 'Update player metrics';

    public function handle(): void
    {
        $user = User::where('User', $this->argument('username'))->firstOrFail();

        $this->info('Update metrics for player ' . $user->username . ' [' . $user->id . ']');
        app()->make(UpdatePlayerMetricsAction::class)->execute($user);
    }
}
