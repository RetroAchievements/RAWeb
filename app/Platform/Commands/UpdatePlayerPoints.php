<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Site\Models\StaticData;
use App\Site\Models\User;
use Illuminate\Console\Command;

class UpdatePlayerPoints extends Command
{
    protected $signature = 'ra:platform:player:update-points {username?}';
    protected $description = 'Calculate player points';

    public function handle(): void
    {
        $username = $this->argument('username');
        if (!empty($username)) {
            $this->calculate($username);

            return;
        }

        $staticData = StaticData::first();

        $userId = $staticData['NextUserIDToScan'];
        for ($i = 0; $i < 3; $i++) {
            /** @var ?User $user */
            $user = User::find($userId);
            if ($user) {
                $this->calculate($user->User);
            }
            // get next highest user ID
            $userId = User::where('ID', '>', $userId)
                ->hasAnyPoints()->min('ID') ?? 1;
        }

        StaticData::first()->update([
            'NextUserIDToScan' => $userId,
        ]);
    }

    private function calculate(string $username): void
    {
        // TODO aggregate player_games instead
        recalculatePlayerPoints($username);
        // TODO queue UpdateDeveloperContributionYield command instead for a more detailed contribution yield update
        recalculateDeveloperContribution($username);
        // TODO (?)
        recalculatePlayerBeatenGames($username);
    }
}
